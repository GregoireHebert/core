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

use ApiPlatform\Exception\MalformedHeadersHttpException;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\Mime\MimeTypes;

/**
 * @author Grégoire Hébert <contact@gheb.dev>
 *
 * @experimental
 *
 * @internal
 */
class MediaTypeFactory implements MediaTypeFactoryInterface
{
    private static MimeTypes $mimeTypes;
    private array $headers = ['CONTENT_TYPE', 'ACCEPT'];

    public function __construct()
    {
        self::$mimeTypes = new MimeTypes([
            // The primary subtype for multipart, "mixed", is intended for use when the body parts are independent and intended to be displayed serially.
            // Any multipart subtypes that an implementation does not recognize should be treated as being of subtype "mixed".
            'multipart/mixed' => ['multipart'],
            // The multipart/alternative type is syntactically identical to multipart/mixed, but the semantics are different.
            // In particular, each of the parts is an "alternative" version of the same information.
            // User agents should recognize that the content of the various parts are interchangeable.
            // The user agent should either choose the "best" type based on the user's environment and preferences, or offer the user the available alternatives.
            'multipart/alternative' => ['multipart'],
            // This type is syntactically identical to multipart/mixed, but the semantics are different.
            // In particular, in a digest, the default Content-Type value for a body part is changed from "text/plain" to "message/rfc822".
            // This is done to allow a more readable digest format that is largely compatible (except for the quoting convention) with RFC 934.
            'multipart/digest' => ['multipart'],
            // This type is syntactically identical to multipart/mixed, but the semantics are different.
            // In particular, in a parallel entity, all the parts are intended to be presented in parallel, i.e., simultaneously,
            // on hardware and software that are capable of doing so.
            // Composing agents should be aware that many mail readers will lack this capability and will show the parts serially in any event.
            'multipart/parallel' => ['multipart'],
        ]);
    }

    public function getMediaType(Request $request, string $header): MediaType
    {
        if (!\in_array($header, $this->headers, true)) {
            throw new \InvalidArgumentException(sprintf('Header %s asked, but expected one of: %s.', $header, implode(', ', $this->headers)));
        }

        if (null === $mediaType = $request->headers->get($header)) {
            throw new MalformedHeadersHttpException('Content Type not found.');
        }

        $parametersStartPosition = strpos($mediaType, ';');
        $typeAndSubType = trim(false !== $parametersStartPosition ? substr($mediaType, 0, $parametersStartPosition) : $mediaType);

        if (0 === \count(self::$mimeTypes->getExtensions($typeAndSubType))) {
            throw new MalformedHeadersHttpException("Content Type $typeAndSubType is invalid.");
        }

        [$type, $subType] = explode('/', $typeAndSubType);
        $parameters = [];

        if (false !== $parametersStartPosition) {
            $parametersString = trim(substr($mediaType, $parametersStartPosition + 1));

            foreach (explode(';', $parametersString) as $parameter) {
                if (!str_contains($parameter, '=')) {
                    throw new MalformedHeadersHttpException('ContentType parameter malformed: keypair `=` separator not found.');
                }

                [$name, $value] = explode('=', trim($parameter));

                $name = strtolower(trim($name));

                // unrecognized parameters must be ignored
                if (null !== ContentTypeParameter::tryFrom($name)) {
                    $parameters[$name] = $value;
                }
            }
        }

        return new MediaType(
            $mediaType,
            $typeAndSubType,
            $type,
            $subType,
            $parameters
        );
    }
}
