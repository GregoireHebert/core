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

use ApiPlatform\Exception\StreamResourceException;
use ApiPlatform\Http\HttpRequestDecoder;
use Symfony\Component\HttpFoundation\Request;

/**
 * @author Grégoire Hébert <contact@gheb.dev>
 *
 * @experimental
 *
 * @internal
 */
final class PartConverter
{
    /**
     * @param false|resource $resource
     */
    public function toRequest(/* resource */ $resource, Request $fromRequest = null): Request
    {
        // PHP does not allow type hinting resources yet.
        if (!\is_resource($resource)) {
            throw new StreamResourceException();
        }

        $parsedHttpRequest = (new HttpRequestDecoder())->parse($resource);

        $serverParameters = $fromRequest ? $fromRequest->server->all() : [];
        $request = $fromRequest ? clone $fromRequest : new Request();

        $request->initialize(
            $parsedHttpRequest->getQuery(),
            $parsedHttpRequest->getRequest(),
            ['_api_odata_subrequest' => true],
            $parsedHttpRequest->getCookies(),
            [],
            array_replace($serverParameters, $parsedHttpRequest->getServer()),
            $parsedHttpRequest->getBody()
        );

        return $request;
    }
}
