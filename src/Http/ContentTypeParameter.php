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
 * HTTP Accept and Content-Type Headers parameters enum.
 *
 * @author Grégoire Hébert <contact@gheb.dev>
 *
 * @experimental
 *
 * @internal
 */
enum ContentTypeParameter: string
{
    case BOUNDARY = 'boundary';
    case CHARSET = 'charset';
}
