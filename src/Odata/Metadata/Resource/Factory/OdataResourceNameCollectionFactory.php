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

use ApiPlatform\Metadata\Resource\Factory\ResourceNameCollectionFactoryInterface;
use ApiPlatform\Metadata\Resource\ResourceNameCollection;
use ApiPlatform\Odata\OdataResource;

/**
 * Register ResourceName for Odata Batch.
 *
 * @author Grégoire Hébert <contact@gheb.dev>
 *
 * @experimental
 */
class OdataResourceNameCollectionFactory implements ResourceNameCollectionFactoryInterface
{
    public const ODATA_RESOURCE_CLASS = OdataResource::class;

    public function __construct(private readonly ?ResourceNameCollectionFactoryInterface $decorated = null)
    {
    }

    /**
     * {@inheritdoc}
     */
    public function create(): ResourceNameCollection
    {
        $classes = [];

        if ($this->decorated) {
            foreach ($this->decorated->create() as $resourceClass) {
                $classes[$resourceClass] = true;
            }
        }

        $classes[self::ODATA_RESOURCE_CLASS] = true;

        return new ResourceNameCollection(array_keys($classes));
    }
}
