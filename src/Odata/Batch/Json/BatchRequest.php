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

/**
 * Represent an Odata Batch Request from Json.
 *
 * @author Grégoire Hébert <contact@gheb.dev>
 *
 * @experimental
 *
 * @internal
 */
final /* readonly */ class BatchRequest implements \IteratorAggregate
{
    /**
     * @var array<Request>
     */
    public readonly array $requests;

    public function __construct(Request ...$requests)
    {
        $this->requests = $requests;
    }

    public function getIterator(): \Traversable
    {
        return new \ArrayIterator($this->requests);
    }
}
