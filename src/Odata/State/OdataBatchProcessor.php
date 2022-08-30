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

namespace ApiPlatform\Odata\State;

use ApiPlatform\Http\MediaTypeFactoryInterface;
use ApiPlatform\Metadata\Operation;
use ApiPlatform\State\ProcessorInterface;
use Symfony\Component\HttpFoundation\RequestStack;
use Symfony\Component\HttpKernel\Exception\BadRequestHttpException;
use Symfony\Component\HttpKernel\Exception\UnsupportedMediaTypeHttpException;

/**
 * @author Grégoire Hébert <contact@gheb.dev>
 *
 * @experimental
 */
final class OdataBatchProcessor implements ProcessorInterface
{
    public function __construct(private readonly RequestStack $requestStack, private readonly MediaTypeFactoryInterface $mediaTypeFactory, private HttpBatchProcessor $httpBatchProcessor, private JsonBatchProcessor $jsonBatchProcessor)
    {
    }

    public function process($data, Operation $operation, array $uriVariables = [], array $context = [])
    {
        if (null === $currentRequest = $this->requestStack->getCurrentRequest()) {
            throw new BadRequestHttpException('No request found to process.');
        }

        $mediaType = $this->mediaTypeFactory->getMediaType($currentRequest, 'CONTENT_TYPE');

        if ('application/json' === $mediaType->typeAndSubType) {
            return $this->jsonBatchProcessor->process($data, $operation, $uriVariables, $context);
        }

        if ('multipart/mixed' === $mediaType->typeAndSubType) {
            return $this->httpBatchProcessor->process($data, $operation, $uriVariables, $context);
        }

        throw new UnsupportedMediaTypeHttpException();
    }
}
