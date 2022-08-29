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
 * A Header as been found missing and can be a security issue.
 *
 * @experimental
 *
 * @author Grégoire Hébert <contact@gheb.dev>
 */
class MissingExpectedHeaderHttpException extends \RuntimeException implements ExceptionInterface
{
}
