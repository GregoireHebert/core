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
use ApiPlatform\Mime\Part\Multipart\BatchPart;
use ApiPlatform\Mime\Part\Multipart\PartConverter;
use ApiPlatform\Mime\Part\Multipart\PartsExtractor;
use ApiPlatform\State\ProcessorInterface;
use Symfony\Component\HttpFoundation\RequestStack;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\HttpKernel\Exception\BadRequestHttpException;
use Symfony\Component\HttpKernel\HttpKernelInterface;
use Symfony\Component\Mime\Part\Multipart\MixedPart;

/**
 * @author Grégoire Hébert <contact@gheb.dev>
 *
 * @experimental
 */
final class OdataBatchProcessor implements ProcessorInterface
{
    private PartConverter $partConverter;

    public function __construct(private RequestStack $requestStack, private HttpKernelInterface $httpKernel, private MediaTypeFactoryInterface $mediaTypeFactory)
    {
        $this->partConverter = new PartConverter();
    }

    public function process($data, Operation $operation, array $uriVariables = [], array $context = [])
    {
        if (null === $currentRequest = $this->requestStack->getCurrentRequest()) {
            throw new BadRequestHttpException('No request found to process.');
        }

        $batchParts = [];

        foreach ((new PartsExtractor($this->mediaTypeFactory))->extract($currentRequest) as $subPart) {
            $response = $this->httpKernel->handle(
                $this->partConverter->toRequest($subPart, $currentRequest), HttpKernelInterface::SUB_REQUEST, false
            );

            $batchParts[] = new BatchPart((string) $response->prepare($currentRequest));
        }

        $mixedPart = new MixedPart(...$batchParts);

        $headers = [
            'Content-Type' => sprintf('%s; charset=utf-8', $mixedPart->getPreparedHeaders()->get('Content-Type')->getBodyAsString()),
            'Vary' => 'Accept',
            'X-Content-Type-Options' => 'nosniff',
            'X-Frame-Options' => 'deny',
        ];

        return new Response(
            $mixedPart->bodyToString(),
            $operation->getStatus(),
            $headers
        );
    }
}
