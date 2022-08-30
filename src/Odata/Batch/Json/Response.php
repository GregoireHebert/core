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

class Response
{
    private function __construct(public readonly string $id, public readonly int $status, public readonly array $headers, public readonly null|string|array $body)
    {
    }

    public static function createFromResponse(string $id, \Symfony\Component\HttpFoundation\Response $response): self
    {
        return new self(
            $id,
            $response->getStatusCode(),
            self::prepareHeaders($response),
            self::prepareBody($response)
        );
    }

    private static function prepareBody(\Symfony\Component\HttpFoundation\Response $response): null|array|string
    {
        $contentType = $response->headers->get('Content-Type');

        /**
         * Accept any application JSON formation and variations
         * @see https://www.iana.org/assignments/media-types/media-types.xhtml#application
         */
        if (preg_match('#application/(?:.*\+)?json#', $contentType)) {
            return json_decode($response->getContent(), true);
        }

        return '' !== ($content = trim($response->getContent() ?: '')) ? $content : null;
    }

    /**
     * Because we are in JSON, we cannot stack headers like set-cookie, so we cumulate them with a coma.
     * @see https://datatracker.ietf.org/doc/rfc9110/ section 5.2
     */
    private static function prepareHeaders(\Symfony\Component\HttpFoundation\Response $response): array
    {
        $headers = $response->headers->all();
        array_walk($headers, static fn (&$header) => $header = implode(', ', $header));

        return $headers;
    }
}
