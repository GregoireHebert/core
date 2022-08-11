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

namespace ApiPlatform\Odata\Metadata\Resource\Factory;

use ApiPlatform\Metadata\ApiResource;
use ApiPlatform\Metadata\Operations;
use ApiPlatform\Metadata\Post;
use ApiPlatform\Metadata\Resource\Factory\ResourceMetadataCollectionFactoryInterface;
use ApiPlatform\Metadata\Resource\ResourceMetadataCollection;
use ApiPlatform\Odata\State\OdataBatchProcessor;

/**
 * Creates a resource metadata for Odata Batch.
 *
 * @author Grégoire Hébert <contact@gheb.dev>
 *
 * @experimental
 */
class OdataResourceMetadataCollectionFactory implements ResourceMetadataCollectionFactoryInterface
{
    public function __construct(private readonly ?ResourceMetadataCollectionFactoryInterface $decorated = null, private readonly bool $batchEnabled = false)
    {
    }

    /**
     * {@inheritdoc}
     */
    public function create(string $resourceClass): ResourceMetadataCollection
    {
        $resourceMetadataCollection = $this->decorated ?
            $this->decorated->create($resourceClass) :
            new ResourceMetadataCollection($resourceClass);

        if (OdataResourceNameCollectionFactory::ODATA_RESOURCE_CLASS !== $resourceClass) {
            return $resourceMetadataCollection;
        }

        $resource = (new ApiResource())
            ->withShortName('odata')
            ->withClass($resourceClass);

        $operations = new Operations();

        if ($this->batchEnabled) {
            $this->buildBatchOperation($operations, $resource);
        }

        $resourceMetadataCollection[] = $resource->withOperations($operations);

        return $resourceMetadataCollection;
    }

    private function buildBatchOperation(Operations $operations, ApiResource $resource): void
    {
        $operationName = sprintf('_api_%s_batch', $resource->getShortName());
        $operation = new Post(
            uriTemplate: '/$batch.{_format}',
            formats: ['multipart' => ['multipart/mixed']],
            inputFormats: ['multipart' => ['multipart/mixed']],
            status: 200,
            openapiContext: [
                'supportedSubmitMethods' => [],
                'summary' => 'allow grouping multiple individual requests into a single HTTP request payload.',
                'description' => 'allow grouping multiple individual requests into a single HTTP request payload.',
                'requestBody' => [
                    'description' => 'Your multipart/mixed request',
                    'content' => [
                        'multipart/mixed;boundary=abc' => [
                            'schema' => [
                                'type' => 'string',
                            ],
                            'example' => <<<HTTP
--abc

a bunch of text

--abc
Content-Type: text/plain

another batch of text

--abc--
HTTP
                        ],
                    ],
                    'required' => true,
                ],
            ],
            shortName: 'odata',
            class: $resource->getClass(),
            deserialize: false,
            validate: false,
            serialize: false,
            name: $operationName,
            processor: OdataBatchProcessor::class,
        );

        $operations->add($operationName, $operation);
    }
}
