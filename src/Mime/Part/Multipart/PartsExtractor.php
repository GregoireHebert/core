<?php

/*
 * This file is part of the API Platform project.
 *
 * (c) Kévin Dunglas <dunglas@gmail.com>
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

declare(strict_types=1);

namespace ApiPlatform\Mime\Part\Multipart;

use ApiPlatform\Exception\StreamResourceException;
use ApiPlatform\Http\MediaType;
use ApiPlatform\Http\MediaTypeFactoryInterface;
use Symfony\Component\HttpFoundation\Request;

/**
 * @author Grégoire Hébert <contact@gheb.dev>
 *
 * @experimental
 *
 * @internal
 */
final class PartsExtractor
{
    private ?MediaType $mediaType = null;

    private bool $boundaryStarted = false;
    private bool $boundaryEnded = false;
    private bool $boundaryNewPart = false;

    private int $subPartOffset = 0;

    public function __construct(private MediaTypeFactoryInterface $mediaTypeFactory)
    {
    }

    /**
     * @param false|resource $resource
     */
    public function extract(Request $request, /* resource */ $resource): \Generator
    {
        // PHP does not allow type hinting resources yet.
        if (!\is_resource($resource)) {
            throw new StreamResourceException();
        }

        $this->mediaType = $this->mediaTypeFactory->getMediaType($request, 'CONTENT_TYPE');

        // if there isn't any boundary, just return the $resource.
        if (null === $this->mediaType->getParameter('boundary')) {
            yield $resource;

            return;
        }

        while (false !== $line = fgets($resource)) {
            // Check for boundary start.
            // There might be comment lines above it and in between.
            if (!$this->boundaryStarted) {
                $this->checkBoundaryStart($line, $resource);
                continue;
            }

            // Is this the end of a part?
            if ($this->checkBoundaryLoop($line)) {
                $currentPosition = ftell($resource) ?? 0;
                $subPartLength = $currentPosition - $this->subPartOffset;

                $subPartStream = fopen('php://temp', 'rw');
                fwrite($subPartStream, stream_get_contents($resource, $subPartLength, $this->subPartOffset));
                rewind($subPartStream);

                // Should we handle sub multipart recursively, or let the routing do its job?
                // yield HttpMultipartSubPartsExtractor::extract($request, $subPartStream);
                yield $subPartStream;

                // If it's the last part, leave.
                if ($this->boundaryEnded) {
                    break;
                }

                $this->subPartOffset = $currentPosition;
                $this->boundaryNewPart = false;
            }
        }
    }

    private function checkBoundaryStart($line, $resource): void
    {
        if (!$this->boundaryStarted && str_starts_with($line, '--'.$this->mediaType->getParameter('boundary'))) {
            $this->subPartOffset = ftell($resource);
            $this->boundaryStarted = true;
        }
    }

    private function checkBoundaryLoop($line): bool
    {
        $this->boundaryNewPart = str_starts_with($line, '--'.$this->mediaType->getParameter('boundary'));
        $this->boundaryEnded = str_starts_with($line, '--'.$this->mediaType->getParameter('boundary').'--');

        return $this->boundaryNewPart || $this->boundaryEnded;
    }
}
