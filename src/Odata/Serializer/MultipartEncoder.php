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

namespace ApiPlatform\Odata\Serializer;

use ApiPlatform\Exception\MalformedHeadersHttpException;
use ApiPlatform\Exception\StreamResourceException;
use ApiPlatform\Http\MediaTypeFactoryInterface;
use ApiPlatform\Mime\Part\Multipart\PartsExtractor;
use Symfony\Component\HttpFoundation\RequestStack;
use Symfony\Component\HttpKernel\Exception\BadRequestHttpException;
use Symfony\Component\HttpKernel\Exception\UnprocessableEntityHttpException;
use Symfony\Component\Mime\Part\Multipart\MixedPart;
use Symfony\Component\Serializer\Encoder\DecoderInterface;
use Symfony\Component\Serializer\Encoder\EncoderInterface;

/**
 * @author Grégoire Hébert <contact@gheb.dev>
 *
 * @experimental
 */
class MultipartEncoder implements EncoderInterface, DecoderInterface
{
    public const FORMAT = 'multipart';

    public function __construct(private RequestStack $requestStack, private MediaTypeFactoryInterface $mediaTypeFactory)
    {
    }

    public function decode(string $data, string $format, array $context = []): \Generator
    {
        try {
            if (null === $currentRequest = $this->requestStack->getCurrentRequest()) {
                throw new BadRequestHttpException('No request found to process.');
            }

            if (false === $currentRequestStream = fopen('php://temp', 'rw')) {
                throw new StreamResourceException();
            }

            fwrite($currentRequestStream, $data);
            rewind($currentRequestStream);

            return (new PartsExtractor($this->mediaTypeFactory))->extract($currentRequest, $currentRequestStream);
        } catch (MalformedHeadersHttpException $e) {
            throw new UnprocessableEntityHttpException($e->getMessage(), $e);
        }
    }

    public function supportsDecoding(string $format): bool
    {
        return self::FORMAT === $format;
    }

    public function encode(mixed $data, string $format, array $context = []): string
    {
        if (null === $request = $this->requestStack->getCurrentRequest()) {
            throw new BadRequestHttpException('No request found to process.');
        }

        if (empty($data)) {
            return '';
        }

        $mixedPart = new MixedPart(...$data);
        $request->attributes->set('_api_odata_content_type', $mixedPart->getPreparedHeaders()->get('Content-Type'));

        return $mixedPart->bodyToString();
    }

    public function supportsEncoding(string $format): bool
    {
        return self::FORMAT === $format;
    }
}
