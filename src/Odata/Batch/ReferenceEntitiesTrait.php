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

namespace ApiPlatform\Odata\Batch;

use Symfony\Component\HttpFoundation\Request;

trait ReferenceEntitiesTrait
{
    private array $entityReferences = [];

    private function getRequestContentId(Request $request)
    {
        return $request->headers->get('CONTENT_ID');
    }

    private function fulfillDependencies(array $dependencies): bool
    {
        if (empty($dependencies)) {
            return true;
        }

        foreach ($dependencies as $dependency) {
            $reference = '$'.$dependency;

            if (!isset($this->entityReferences[$reference])){
                return false;
            }
        }

        return true;
    }

    private function storeNewEntityForReference(Request $request): void
    {
        if (
            'POST' === $request->getMethod() &&
            null !== ($contentId = $this->getRequestContentId($request)) &&
            null !== ($itemIri = $request->attributes->get('_api_write_item_iri'))
        ) {
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

        return $request->duplicate(null, null, null, null, null, $server);
    }
}
