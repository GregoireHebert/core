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

namespace ApiPlatform\Http;

/**
 * Represent an HTTP Internet media types [RFC2046].
 *
 * @author Grégoire Hébert <contact@gheb.dev>
 *
 * @experimental
 *
 * @internal
 *
 * @see MediaTypeFactory
 */
final class MediaType
{
    /**
     * @param array<string,string> $parameters
     */
    public function __construct(
        public readonly string $mediaType = '',
        public readonly string $typeAndSubType = '',
        public readonly string $type = '',
        public readonly string $subType = '',
        public array $parameters = [],
    ) {
    }

    public function getParameter($name, $default = null)
    {
        return $this->parameters[$name] ?? $default;
    }
}
