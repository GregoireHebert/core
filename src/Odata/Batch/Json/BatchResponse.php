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
 * Represent an Odata Batch Response in Json.
 *
 * @author Grégoire Hébert <contact@gheb.dev>
 *
 * @experimental
 *
 * @internal
 */
final /* readonly */ class BatchResponse
{
    /**
     * @var array<Response>
     */
    public readonly array $responses;

    public function __construct(Response ...$responses)
    {
        $this->responses = $responses;
    }
}
