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
 * According to the HTTP standard, each header field is comprised of a name (e.g. Content-Type, Content-Length, etc.)
 * followed by a colon (’:’) and then its value (for instance: Content-Length: 8).
 * When we detect a request where one or more of its headers do not comply to this standard then this exception is generated.
 * This violation indicates that a request was specially crafted with this malformed header.
 * These types of attacks are usually aimed at fooling or harming the parsing mechanism of the server.
 *
 * Possible Attacks
 *
 * This type of maliciously crafted request is usually carried out in order to attack the web server’s parsing mechanism,
 * as part of a Buffer Overflow, Denial of Service, Source Code Disclosure or Takeover of Server attack.
 *
 * @experimental
 *
 * @author Grégoire Hébert <contact@gheb.dev>
 */
class MalformedHeadersHttpException extends \RuntimeException implements ExceptionInterface
{
}
