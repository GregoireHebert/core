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

use ApiPlatform\Mime\Part\Multipart\BatchPart;
use ApiPlatform\Mime\Part\Multipart\PartConverter;
use Symfony\Component\HttpFoundation\RequestStack;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\HttpKernel\Exception\BadRequestHttpException;
use Symfony\Component\Serializer\Normalizer\DenormalizerInterface;
use Symfony\Component\Serializer\Normalizer\NormalizerInterface;

/**
 * @author Grégoire Hébert <contact@gheb.dev>
 *
 * @experimental
 */
class MultipartNormalizer implements NormalizerInterface, DenormalizerInterface
{
    public const FORMAT = 'multipart';

    public function __construct(private RequestStack $requestStack)
    {
    }

    public function denormalize(mixed $data, string $type, string $format = null, array $context = []): \Generator
    {
        if (null === $currentRequest = $this->requestStack->getCurrentRequest()) {
            throw new BadRequestHttpException('No request found to process.');
        }

        foreach ($data as $subPart) {
            yield (new PartConverter())->toRequest($subPart, $currentRequest);
        }
    }

    public function supportsDenormalization(mixed $data, string $type, string $format = null): bool
    {
        return self::FORMAT === $format;
    }

    public function normalize(mixed $object, string $format = null, array $context = [])
    {
        if (null === $currentRequest = $this->requestStack->getCurrentRequest()) {
            throw new BadRequestHttpException('No request found to process.');
        }

        $parts = [];
        /** @var Response $response */
        foreach ($object as $response) {
            $response->prepare($currentRequest);

            $parts[] = new BatchPart((string) $response->prepare($currentRequest));
        }

        return $parts;
    }

    public function supportsNormalization(mixed $data, string $format = null)
    {
        return self::FORMAT === $format;
    }
}
