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

namespace ApiPlatform\Odata\Batch\Json;

use ApiPlatform\Mime\Part\Multipart\PartsExtractorInterface;
use Symfony\Component\HttpFoundation\Request as HttpRequest;
use Symfony\Component\Serializer\SerializerInterface;

/**
 * @author Grégoire Hébert <contact@gheb.dev>
 *
 * @experimental
 *
 * @internal
 */
final class PartsExtractor implements PartsExtractorInterface
{
    public function __construct(private SerializerInterface $serializer)
    {
    }

    /**
     * @return iterable<Request>
     */
    public function extract(HttpRequest $request): iterable
    {
        /** @var BatchRequest $batchRequest */
        return $this->serializer->deserialize($request->getContent(false), BatchRequest::class, 'json');
    }
}
