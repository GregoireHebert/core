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

use ApiPlatform\Exception\MalformedHeadersHttpException;
use ApiPlatform\Http\MediaTypeFactoryInterface;
use ApiPlatform\Metadata\Operation;
use ApiPlatform\Mime\Part\Multipart\BatchPart;
use ApiPlatform\Mime\Part\Multipart\MixedPart;
use ApiPlatform\Mime\Part\Multipart\PartConverter;
use ApiPlatform\Mime\Part\Multipart\PartsExtractor;
use ApiPlatform\State\ProcessorInterface;
use Symfony\Component\HttpFoundation\AcceptHeader;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\RequestStack;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\HttpFoundation\StreamedResponse;
use Symfony\Component\HttpKernel\Exception\BadRequestHttpException;
use Symfony\Component\HttpKernel\HttpKernelInterface;

/**
 * @author Grégoire Hébert <contact@gheb.dev>
 *
 * @experimental
 */
final class OdataBatchProcessor implements ProcessorInterface
{
    private PartConverter $partConverter;
    private Request $currentRequest;
    private MixedPart $mixedPart;

    private array $entityReferences = [];

    /**
     * The continue-on-error preference on a batch request is used to request whether, upon encountering a request
     * within the batch that returns an error, the service return the error for that request and continue processing
     * additional requests within the batch (if specified with an implicit or explicit value of true),
     * or rather stop further processing (if specified with an explicit value of false)
     */
    private bool $continueOnError = false;

    public function __construct(private RequestStack $requestStack, private HttpKernelInterface $httpKernel, private MediaTypeFactoryInterface $mediaTypeFactory)
    {
        $this->partConverter = new PartConverter();
    }

    public function process($data, Operation $operation, array $uriVariables = [], array $context = [])
    {
        if (null === $this->currentRequest = $this->requestStack->getCurrentRequest()) {
            throw new BadRequestHttpException('No request found to process.');
        }

        try {
            // initialize current request parts
            $this->entityReferences = [];
            $this->mixedPart = new MixedPart();

            $preferHeader = AcceptHeader::fromString($this->currentRequest->headers->get('Prefer'));
            $this->continueOnError = $preferHeader->has('continue-on-error') || $preferHeader->has('odata.continue-on-error');

            $headers = [
                'Content-Type' => sprintf('%s; charset=utf-8', $this->mixedPart->getPreparedHeaders()->get('Content-Type')->getBodyAsString()),
                'Vary' => 'Accept, Prefer',
                'X-Content-Type-Options' => 'nosniff',
                'X-Frame-Options' => 'deny',
            ];

            if ($this->currentRequest->attributes->get('_api_odata_subrequest', false)) {
                return new Response(
                    $this->getPreparedMixedPart()->bodyToString(),
                    $operation->getStatus(),
                    $headers
                );
            }

            // If the set of request headers of a batch request are valid the service MUST return a 200 OK HTTP response code
            // to indicate that the batch request was accepted for processing, but the processing is yet to be completed.
            // The individual requests within the body of the batch request may subsequently fail or be malformed;
            // however, this enables batch implementations to stream the results.
            return new StreamedResponse(
                clone $this,
                $operation->getStatus(),
                $headers
            );
        } catch (MalformedHeadersHttpException $e) {
            // If the service receives a batch request with an invalid set of headers
            // it MUST return a 4xx response code and perform no further processing of the batch request.
            throw new BadRequestHttpException($e->getMessage(), $e);
        }
    }

    public function __invoke(): void
    {
        echo $this->getPreparedMixedPart()->bodyToString();
        flush();
    }

    private function getPreparedMixedPart(): MixedPart
    {
        foreach ((new PartsExtractor($this->mediaTypeFactory))->extract($this->currentRequest) as $subPart) {
            $request = $this->partConverter->toRequest($subPart, $this->currentRequest);
            $request = $this->referencingNewEntities($request);

            $response = $this->httpKernel->handle($request, HttpKernelInterface::SUB_REQUEST, $this->continueOnError)->prepare($this->currentRequest);

            $this->storeNewEntityForReference($request);

            $this->mixedPart->addPart(new BatchPart((string) $response));
        }

        return $this->mixedPart;
    }

    private function storeNewEntityForReference(Request $request): void
    {
        if (
            $request->getMethod() === 'POST' &&
            null !== ($contentId = $request->headers->get('content-id')) &&
            null !== ($itemIri = $request->attributes->get('_api_write_item_iri'))
        ){
            $this->entityReferences['$'.$contentId] = $itemIri;
        }
    }

    /**
     * Entities created by an Insert request can be referenced in the request URL of subsequent requests within the same change set.
     */
    private function referencingNewEntities(Request $request): Request
    {
        $server = $request->server->all();

        $server['REQUEST_URI'] = str_replace(array_keys($this->entityReferences), array_values($this->entityReferences), $server['REQUEST_URI']);
        $server['PATH_INFO'] = str_replace(array_keys($this->entityReferences), array_values($this->entityReferences), $server['REQUEST_URI']);

        return $request->duplicate(null,null,null,null,null, $server);
    }
}
