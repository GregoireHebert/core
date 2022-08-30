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

use ApiPlatform\Exception\FailedDependencyHttpException;
use ApiPlatform\Exception\InvalidOdataIndividualRequestException;
use ApiPlatform\Exception\MalformedHeadersHttpException;
use ApiPlatform\Exception\MissingExpectedHeaderHttpException;
use ApiPlatform\Metadata\Operation;
use ApiPlatform\Odata\Batch\Json\BatchResponse;
use ApiPlatform\Odata\Batch\Json\PartConverter;
use ApiPlatform\Odata\Batch\Json\PartsExtractor as JsonPartsExtractor;
use ApiPlatform\Odata\Batch\Json\Response;
use ApiPlatform\Odata\Batch\ReferenceEntitiesTrait;
use ApiPlatform\State\ProcessorInterface;
use Symfony\Component\HttpFoundation\AcceptHeader;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\RequestStack;
use Symfony\Component\HttpKernel\Exception\BadRequestHttpException;
use Symfony\Component\HttpKernel\HttpKernelInterface;
use Symfony\Component\Serializer\SerializerInterface;

/**
 * API Platform processor for JSON batch request.
 *
 * @author Grégoire Hébert <contact@gheb.dev>
 *
 * @see http://docs.oasis-open.org/odata/odata-json-format/v4.01/odata-json-format-v4.01.html#sec_BatchRequestsandResponses
 *
 * @experimental
 *
 * @internal
 */
class JsonBatchProcessor implements ProcessorInterface
{
    use ReferenceEntitiesTrait;

    private Request $currentRequest;

    private PartConverter $partConverter;

    /**
     * The continue-on-error preference on a batch request is used to request whether, upon encountering a request
     * within the batch that returns an error, the service return the error for that request and continue processing
     * additional requests within the batch (if specified with an implicit or explicit value of true),
     * or rather stop further processing (if specified with an explicit value of false).
     *
     * it's expected to be express through the `Prefer` Header with the attribute `continue-on-error` or `odata.continue-on-error`.
     */
    private bool $continueOnError;

    public function __construct(private RequestStack $requestStack, private HttpKernelInterface $httpKernel, private SerializerInterface $serializer)
    {
        $this->partConverter = new PartConverter();
    }

    public function process($data, Operation $operation, array $uriVariables = [], array $context = [])
    {
        if (null === $this->currentRequest = $this->requestStack->getCurrentRequest()) {
            throw new BadRequestHttpException('No request found to process.');
        }

        try {
            // json Batch Processor cannot contain batch requests itself.
            if ($this->currentRequest->attributes->get('_api_odata_subrequest', false)) {
                throw new BadRequestHttpException();
            }

            // initialize current request parts
            $this->entityReferences = [];

            $preferHeader = AcceptHeader::fromString($this->currentRequest->headers->get('Prefer'));
            $this->continueOnError = $preferHeader->has('continue-on-error') || $preferHeader->has('odata.continue-on-error');

            return new JsonResponse(
                $this->getResponse(),
                $operation->getStatus(),
                [
                    'Content-Type' => 'application/json; charset=utf-8',
                    'Vary' => 'Accept, Prefer',
                    'X-Content-Type-Options' => 'nosniff',
                    'X-Frame-Options' => 'deny',
                ]
            );
        } catch (MalformedHeadersHttpException|InvalidOdataIndividualRequestException|MissingExpectedHeaderHttpException $e) {
            // If the service receives a batch request with an invalid set of headers
            // it MUST return a 4xx response code and perform no further processing of the batch request.
            throw new BadRequestHttpException($e->getMessage(), $e);
        }
    }

    private function getResponse(): BatchResponse
    {
        $responses = [];
        foreach ((new JsonPartsExtractor($this->serializer))->extract($this->currentRequest) as $subPart) {
            // If one of the requests it depends on has failed, the dependent request is not executed
            // and a response with status code of 424 Failed Dependency is returned for it as part of the batch response.
            // unless continue-on-error preference has been specified.
            if (!$this->continueOnError && !$this->fulfillDependencies($subPart->getDependsOn())) {
                throw new FailedDependencyHttpException();
            }

            $request = $this->partConverter->toRequest($subPart, $this->currentRequest);

            if (!$request instanceof Request) {
                throw new InvalidOdataIndividualRequestException();
            }

            $request = $this->referencingNewEntities($request);

            $response = $this->httpKernel->handle($request, HttpKernelInterface::SUB_REQUEST, $this->continueOnError)->prepare($this->currentRequest);

            $this->storeNewEntityForReference($request);

            $responses[] = Response::createFromResponse($request->headers->get('Content-Id'), $response);
        }

        return new BatchResponse(...$responses);
    }
}
