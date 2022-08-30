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

use Symfony\Component\HttpFoundation\Request as HttpRequest;

/**
 * Convert an JSON Batch individual request into a Symfony Http-Foundation/Request.
 *
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
    public function toRequest(Request $request, HttpRequest $fromRequest = null): HttpRequest
    {
        $serverParameters = $fromRequest ? $fromRequest->server->all() : [];
        $httpRequest = $fromRequest ? clone $fromRequest : new HttpRequest();

        $httpRequest->initialize(
            $request->getQuery(),
            $request->getRequest(),
            ['_api_odata_subrequest' => true],
            $request->getCookies(),
            [],
            array_replace($serverParameters, $request->getServer()),
            $request->getBodyString()
        );


        return $httpRequest;
    }
}
