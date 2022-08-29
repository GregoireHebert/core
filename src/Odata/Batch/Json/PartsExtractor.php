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

namespace ApiPlatform\Odata\Batch\Json;

use ApiPlatform\Mime\Part\Multipart\PartsExtractorInterface;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\Serializer\SerializerInterface;

/**
 * @author Grégoire Hébert <contact@gheb.dev>
 */
final class PartsExtractor implements PartsExtractorInterface
{
    public function __construct(private SerializerInterface $serializer)
    {
    }

    public function extract(Request $request): \Generator
    {
        $jsonContent = $request->getContent();

        /** @var BatchRequest $batchRequest */
        $batchRequest = $this->serializer->deserialize($jsonContent, BatchRequest::class, 'json');
        $serverParameters = $request->server->all();

        foreach ($batchRequest->requests as $individualRequest) {
            $request = clone $request;

            $request->initialize(
                $individualRequest->getQuery(),
                $individualRequest->getRequest(),
                ['_api_odata_subrequest' => true],
                $individualRequest->getCookies(),
                [],
                array_replace($serverParameters, $individualRequest->getServer()),
                $individualRequest->getBodyString()
            );

            yield $request;
        }
    }
}
