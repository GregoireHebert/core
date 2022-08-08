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

namespace ApiPlatform\Exception;

/**
 * @author Grégoire Hébert <contact@gheb.dev>
 */
class StreamResourceException extends \RuntimeException implements ExceptionInterface
{
    protected $message = 'Impossible to open or read the stream.';
}
