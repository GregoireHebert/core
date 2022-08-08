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

use ApiPlatform\Metadata\Operation;
use ApiPlatform\State\ProcessorInterface;
use Doctrine\Common\Collections\ArrayCollection;
use Symfony\Component\HttpKernel\HttpKernelInterface;

/**
 * @author Grégoire Hébert <contact@gheb.dev>
 *
 * @experimental
 */
final class OdataBatchProcessor implements ProcessorInterface
{
    public function __construct(private HttpKernelInterface $httpKernel)
    {
    }

    public function process($data, Operation $operation, array $uriVariables = [], array $context = [])
    {
        if (!$data instanceof \Generator) {
            throw new \InvalidArgumentException('$data must be a Generator to call each subrequest one at one;');
        }

        $responses = new ArrayCollection();

        foreach ($data as $i => $subRequest) {
            $responses->set($i, $this->httpKernel->handle($subRequest, HttpKernelInterface::SUB_REQUEST));
            // TODO ODATA CHECKS
        }

        return $responses;
    }
}
