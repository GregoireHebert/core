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

use Symfony\Component\HttpFoundation\Request;

/**
 * HTTP uses Internet media types [RFC2046] in the Content-Type
 * (Section 3.1.1.5) and Accept (Section 5.3.2) header fields in order
 * to provide open and extensible data typing and type negotiation.
 * Media types define both a data format and various processing models:
 * how to process that data in accordance with each context in which it
 * is received.
 *
 * media-type = type "/" subtype *( OWS ";" OWS parameter )
 * type       = token
 * subtype    = token
 *
 * The type/subtype MAY be followed by parameters in the form of
 * name=value pairs.
 *
 * parameter      = token "=" ( token / quoted-string )
 *
 * @author Grégoire Hébert <contact@gheb.dev>
 *
 * @experimental
 *
 * @internal
 */
interface MediaTypeFactoryInterface
{
    public function getMediaType(Request $request, string $header): MediaType;
}
