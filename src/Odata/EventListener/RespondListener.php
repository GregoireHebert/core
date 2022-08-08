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

namespace ApiPlatform\Odata\EventListener;

use ApiPlatform\Odata\OdataResource;
use ApiPlatform\Util\OperationRequestInitiatorTrait;
use Symfony\Component\HttpKernel\Event\ViewEvent;
use ApiPlatform\Symfony\EventListener\RespondListener as BaseListener;

/**
 * @author Grégoire Hébert <contact@gheb.dev>
 *
 * @experimental
 */
class RespondListener
{
    use OperationRequestInitiatorTrait;

    public function __construct(private BaseListener $respondListener)
    {
    }

    public function onKernelView(ViewEvent $event): void
    {
        $this->respondListener->onKernelView($event);

        $request = $event->getRequest();
        $operation = $this->initializeOperation($request);

        if ((null !== $response = $event->getResponse()) && $operation?->getClass() === OdataResource::class && null !== ($contentType = $request->attributes->get('_api_odata_content_type'))) {
            $response->headers->set('content-type', [$contentType->getBodyAsString()]);
        }
    }
}
