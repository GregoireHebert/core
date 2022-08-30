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

use ApiPlatform\Mime\Part\Multipart\HeaderParser;

/**
 * Represent an individual Request from Odata batch in JSON.
 * Note: an individual request MUST NOT itself be a batch request.
 *
 * @author Grégoire Hébert <contact@gheb.dev>
 *
 * @experimental
 *
 * @internal
 */
final /* readonly */ class Request
{
    private array $server = [];
    private array $cookies = [];
    private array $get = [];
    private array $post = [];

    public function __construct(
        private readonly string $id, // name/value pair corresponds to the Content-ID header in the multipart batch
        private readonly string $method, // is a string that MUST contain one of the literals delete, get, patch, post, or put
        private readonly string $url, // is a string containing the individual request URL
        private readonly array $dependsOn = [], // array of strings whose values MUST be values of either id or atomicityGroup (not supported yet) of preceding request objects; forward references are not allowed.
//        public readonly string $atomicityGroup,
//        public readonly string $if,
        private readonly array $headers = [], // is an object whose name/value pairs represent request headers. it MUST contain a name/value pair with the name content-type whose value is the media type
        private readonly null|string|array $body = null // can be null, which is equivalent to not specifying the body name/value pair.
    ) {
    }

    public function getDependsOn(): array
    {
        return $this->dependsOn;
    }

    public function getQuery(): array
    {
        return $this->get;
    }

    public function getRequest(): array
    {
        return $this->post;
    }

    public function getCookies(): array
    {
        if (empty($this->server)) {
            $this->parseHeaders();
        }

        return $this->cookies;
    }

    public function getServer(): array
    {
        if (empty($this->server)) {
            $this->parseHeaders();
        }

        return $this->server;
    }

    public function getBodyString(): string
    {
        if (\is_array($this->body) || \is_object($this->body)) {
            return json_encode($this->body, \JSON_THROW_ON_ERROR);
        }

        return $this->body ?? '';
    }

    private function parseHeaders(): void
    {
        HeaderParser::reset();

        foreach ($this->headers as $headerName => $value) {
            HeaderParser::parse($headerName, $value);
        }

        $parsedHeaders = HeaderParser::getParsedHeaders();
        $this->server['HTTP_CONTENT_ID'] = $this->id;
        $this->server['REQUEST_METHOD'] = $this->method;
        $this->server['REQUEST_URI'] = $this->url;
        $this->server['SERVER_PROTOCOL'] = 'HTTP/1.1';

        $this->parsePath();

        if ('' !== $this->body && \in_array($this->server['REQUEST_METHOD'], ['POST', 'PUT'], true) && false !== stripos($this->server['CONTENT_TYPE'] ?? '', 'application/x-www-form-urlencoded')) {
            parse_str($this->body, $this->post);
        }

        $this->server += $parsedHeaders['server'];
        $this->cookies += $parsedHeaders['cookies'];
    }

    private function parsePath(): void
    {
        if (str_contains($this->server['REQUEST_URI'], '?')) {
            [$path, $queryString] = explode('?', $this->server['REQUEST_URI'], 2);
            $this->server['PATH_INFO'] = $path;
            $this->parseQueryString($queryString);
        }
    }

    private function parseQueryString(string $queryString): void
    {
        if ('' === $queryString) {
            return;
        }

        foreach (explode('&', $queryString) as $parameter) {
            [$key, $value] = explode('=', $parameter, 2);

            // PHP replaces spaces by an underscore
            $this->get[str_replace(' ', '_', urldecode(trim($key)))] = urldecode(trim($value));
        }
    }
}
