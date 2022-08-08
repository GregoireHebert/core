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

use Symfony\Component\Mime\Part\AbstractPart;

/**
 * @author Grégoire Hébert <contact@gheb.dev>
 *
 * @experimental
 *
 * @internal
 */
final class BatchPart extends AbstractPart
{
    public function __construct(string $body)
    {
        parent::__construct();

        $this->body = $body;
    }

    public function toString(): string
    {
        return $this->bodyToString();
    }

    public function bodyToString(): string
    {
        return $this->body;
    }

    public function bodyToIterable(): iterable
    {
        yield $this->body;
    }

    public function getMediaType(): string
    {
        return '';
    }

    public function getMediaSubtype(): string
    {
        return '';
    }
}
