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
final class PartsExtractor implements PartsExtractorInterface
{
    private ?MediaType $mediaType = null;

    private bool $boundaryStarted = false;
    private bool $boundaryEnded = false;
    private bool $boundaryNewPart = false;

    private int $subPartOffset = 0;

    public function __construct(private MediaTypeFactoryInterface $mediaTypeFactory)
    {
    }

    public function extract(Request $request): \Generator
    {
        $this->mediaType = $this->mediaTypeFactory->getMediaType($request, 'CONTENT_TYPE');

        $resource = $request->getContent(true);

        if (!\is_resource($resource)) {
            throw new StreamResourceException();
        }

        // if there isn't any boundary, just return the $resource.
        if (null === $this->mediaType->getParameter('boundary')) {
            yield $resource;

            return;
        }

        $previousLinePosition = 0;

        while (false !== $line = fgets($resource)) {
            // Check for boundary start.
            // There might be comment lines above it and in between.
            if (!$this->boundaryStarted) {
                $this->checkBoundaryStart($line, $resource);
                continue;
            }

            // Is this the end of a part?
            if (!$this->checkBoundaryLoop($line)) {
                $previousLinePosition = ftell($resource);
                continue;
            }

            $subPartLength = $previousLinePosition - $this->subPartOffset;
            $currentPosition = ftell($resource);

            $subPartStream = fopen('php://temp', 'rw');
            fwrite($subPartStream, stream_get_contents($resource, $subPartLength, $this->subPartOffset));
            rewind($subPartStream);

            // we could handle sub multipart recursively, but I chose to let the routing do its job, to allow external calls.
            // yield HttpMultipartSubPartsExtractor::extract($request, $subPartStream);
            yield $subPartStream;

            // If it's the last part, leave.
            if ($this->boundaryEnded) {
                break;
            }

            fseek($resource, $currentPosition);
            $this->subPartOffset = $currentPosition;
            $this->boundaryNewPart = false;
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
