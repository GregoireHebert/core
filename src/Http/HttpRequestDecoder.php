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

use ApiPlatform\Exception\MissingExpectedHeaderHttpException;
use ApiPlatform\Mime\Part\Multipart\HeaderParser;
use Symfony\Component\HttpKernel\Exception\BadRequestHttpException;

/**
 * @author Grégoire Hébert <contact@gheb.dev>
 *
 * @experimental
 *
 * @internal
 */
class HttpRequestDecoder
{
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
                $this->extractStartLine($line) ||
                $this->extractHeader($line)
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

    private function extractStartLine($line): bool
    {
        preg_match(self::START_LINE_REGEX, $line, $matches, \PREG_UNMATCHED_AS_NULL);

        if (0 !== \count($matches)) {
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
            return "\n" === $line;
        }

        return true;
    }

    private function extractHeader($line): bool
    {
        preg_match(self::HEADER_REGEX, $line, $matches, \PREG_UNMATCHED_AS_NULL);

        if (0 !== \count($matches)) {
            $this->headers[] = $matches['header'];

            return true;
        }

        return false;
    }

    private function parseBody(): void
    {
        if ('' === $this->body || !\in_array($this->server['REQUEST_METHOD'], ['POST', 'PUT'], true)) {
            return;
        }

        if (null === ($this->server['CONTENT_TYPE'] ?? $this->server['HTTP_CONTENT_TYPE'] ?? null)) {
            throw new MissingExpectedHeaderHttpException('
                The Content-Type header was found to be empty or missing on one or more of your pages.
                This means that the attacker is able to prepare the code that will be treated by the user’s browser as part of the web page and executed.
                Therefore the adversaries can attack your web application by modifying the look of your web page or
                stealing user’s data which may then lead to further Cross-Site Scripting attacks (see XSS).
            ');
        }

        if (false !== stripos($this->server['CONTENT_TYPE'] ?? $this->server['HTTP_CONTENT_TYPE'], 'application/x-www-form-urlencoded')) {
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

        HeaderParser::reset();

        foreach ($headers as $header) {
            [$name, $value] = explode(':', $header, 2);

            HeaderParser::parse($name, $value);
        }

        $parsedHeaders = HeaderParser::getParsedHeaders();
        $this->server += $parsedHeaders['server'];
        $this->cookies += $parsedHeaders['cookies'];
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
