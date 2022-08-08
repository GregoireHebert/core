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

use Symfony\Component\HttpKernel\Exception\BadRequestHttpException;

/**
 * @author Grégoire Hébert <contact@gheb.dev>
 *
 * @experimental
 *
 * @internal
 */
class HttpParser
{
    private const AUTHORIZATION_REGEX = '/(?<scheme>.*) (?<value>.*)$/Ui';
    private const START_LINE_REGEX = "/(?<method>(?:HEAD|GET|POST|PATCH|PUT|DELETE|PURGE|OPTIONS|TRACE|CONNECT))\s(?<path>.+)\s(?<version>HTTP\/.+)\n/";
    private const HEADER_REGEX = "/(?<header>(?:[-!#-'*+.0-9A-Z^-z|~]+:.*(?:\n){0,1})+)/";

    private array $server = [];
    private array $headers = [];
    private array $cookies = [];
    private array $get = [];
    private array $post = [];
    private string $body = '';

    private bool $bodyStarted = false;

    private bool $startLineFound = false;

    public function __construct()
    {
    }

    public function parse(/* resource */ $resource)
    {
        while (false !== $line = fgets($resource)) {
            $this->parseLine($line);
        }

        $this->parseBody();

        if (!$this->startLineFound) {
            throw new BadRequestHttpException();
        }

        return $this;
    }

    public function parseLine(string $line): void
    {
        if (
            !$this->bodyStarted &&
            (
                $this->isStartLine($line) ||
                $this->isHeader($line)
            )
        ) {
            // stack the start line and headers.
            return;
        }

        if (!$this->bodyStarted && $this->isEmptyLine($line)) {
            // now that the body starts, we can parse the headers
            $this->bodyStarted = true;
            $this->parseHeaders();

            return;
        }

        // if we hit something not being a header nor a start line before the body has started.
        // it will negate the all process.
        if (!$this->bodyStarted) {
            throw new BadRequestHttpException();
        }

        $this->body .= $line;
    }

    private function isStartLine($line): bool
    {
        preg_match(self::START_LINE_REGEX, $line, $matches, \PREG_UNMATCHED_AS_NULL);

        if (!empty($matches)) {
            $this->server['REQUEST_METHOD'] = $matches['method'];
            $this->server['REQUEST_URI'] = $matches['path'];
            $this->server['SERVER_PROTOCOL'] = $matches['version'];

            $this->parsePath();

            $this->startLineFound = true;

            return true;
        }

        return false;
    }

    private function isEmptyLine($line): bool
    {
        if (!$this->bodyStarted) {
            return $this->bodyStarted = "\n" === $line;
        }

        return true;
    }

    private function isHeader($line): bool
    {
        preg_match(self::HEADER_REGEX, $line, $matches, \PREG_UNMATCHED_AS_NULL);

        if (!empty($matches)) {
            $this->headers[] = $matches['header'];

            return true;
        }

        return false;
    }

    private function parseBody(): void
    {
        if ('' === $this->body || 'POST' !== $this->server['REQUEST_METHOD']) {
            return;
        }

        if (false !== stripos($this->server['CONTENT_TYPE'], 'application/x-www-form-urlencoded')) {
            parse_str($this->body, $this->post);
        }

        // Note: files are not handled for now.
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

    private function parseHeaders(): void
    {
        $headers = array_filter(array_map('trim', $this->headers));

        foreach ($headers as $header) {
            [$name, $value] = explode(':', $header, 2);

            $name = strtolower(trim($name));
            $value = trim($value);

            $normalizedName = strtoupper(str_replace('-', '_', $name));

            switch ($name) {
                case 'content-type':
                case 'content-disposition':
                    // CONTENT_TYPE is set with and without HTTP_ prefix
                    $this->server[$normalizedName] = trim($value);
                    break;
                case 'content-length':
                    // CONTENT_LENGTH is set with and without HTTP_ prefix
                    $this->server[$normalizedName] = $value;
                    break;
                case 'authorization':
                    preg_match(self::AUTHORIZATION_REGEX, $value, $matches);
                    $scheme = strtolower($matches['scheme']);

                    switch ($scheme) {
                        case 'basic':
                            [$user, $password] = explode(':', base64_decode($matches['value'], true), 2);
                            $this->server['PHP_AUTH_USER'] = $user;
                            $this->server['PHP_AUTH_PW'] = $password;
                            break;
                        case 'digest':
                            $this->server['PHP_AUTH_DIGEST'] = $matches['value'];
                            break;
                        default:
                            throw new \RuntimeException(sprintf('"%s" authorization scheme not implemented', $scheme));
                    }
                    break;
                case 'cookie':
                    $this->parseCookie($value);
                    break;
            }

            $this->server['HTTP_'.$normalizedName] = $value;
        }
    }

    private function parseCookie(string $value): void
    {
        foreach (explode(';', $value) as $cookie) {
            [$name, $value] = explode('=', $cookie, 2);
            $this->cookies[str_replace(' ', '_', urldecode(trim($name)))] = urldecode(trim($value));
        }
    }

    public function getHeaders(): array
    {
        return $this->headers;
    }

    public function getServer(): array
    {
        return $this->server;
    }

    public function getQuery(): array
    {
        return $this->get;
    }

    public function getCookies(): array
    {
        return $this->cookies;
    }

    public function getBody(): string
    {
        return $this->body;
    }

    public function getRequest(): array
    {
        return $this->post;
    }
}
