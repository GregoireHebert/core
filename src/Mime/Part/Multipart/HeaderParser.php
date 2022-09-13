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

use Symfony\Component\HttpFoundation\HeaderUtils;

/**
 * @author Grégoire Hébert <contact@gheb.dev>
 *
 * @experimental
 *
 * @internal
 */
final class HeaderParser
{
    private const AUTHORIZATION_REGEX = '/(?<scheme>.*) (?<value>.*)$/Ui';
    private static array $parsedHeaders = ['server' => [], 'cookies' => []];

    public static function parse(string $name, string $headerValue): void
    {
        $normalizedName = strtoupper(str_replace('-', '_', $name));
        $headerValue = trim($headerValue);

        if (str_contains($headerValue, ',')) {
            // Header can be coma separated
            foreach (HeaderUtils::split($headerValue ?? '', ',') as $value) {
                self::doParse($normalizedName, $value);
            }
        } else {
            self::doParse($normalizedName, $headerValue);
        }
    }

    private static function doParse(string $normalizedName, string $value)
    {
        switch ($normalizedName) {
            case 'CONTENT_TYPE':
            case 'CONTENT_DISPOSITION':
                // CONTENT_TYPE is set with and without HTTP_ prefix
                self::$parsedHeaders['server'][$normalizedName] = $value;
                break;
            case 'CONTENT_LENGTH':
                // CONTENT_LENGTH is set with and without HTTP_ prefix
                self::$parsedHeaders['server'][$normalizedName] = $value;
                break;
            case 'AUTHORIZATION':
                preg_match(self::AUTHORIZATION_REGEX, $value, $matches);
                $scheme = strtolower($matches['scheme']);

                switch ($scheme) {
                    case 'basic':
                        [$user, $password] = explode(':', base64_decode($matches['value'], true), 2);
                        self::$parsedHeaders['server']['PHP_AUTH_USER'] = $user;
                        self::$parsedHeaders['server']['PHP_AUTH_PW'] = $password;
                        break;
                    case 'digest':
                        self::$parsedHeaders['server']['PHP_AUTH_DIGEST'] = $matches['value'];
                        break;
                    default:
                        throw new \RuntimeException(sprintf('"%s" authorization scheme not implemented', $scheme));
                }
                break;
            case 'COOKIE':
                self::parseCookie($value);
                break;
        }

        self::$parsedHeaders['server']['HTTP_'.$normalizedName] = $value;
    }

    public static function getParsedHeaders(): array
    {
        return self::$parsedHeaders;
    }

    public static function reset(): void
    {
        self::$parsedHeaders = ['server' => [], 'cookies' => []];
    }

    private static function parseCookie(string $value): void
    {
        foreach (explode(';', $value) as $cookie) {
            [$name, $value] = explode('=', $cookie, 2);
            self::$parsedHeaders['cookies'][str_replace(' ', '_', urldecode(trim($name)))] = urldecode(trim($value));
        }
    }
}
