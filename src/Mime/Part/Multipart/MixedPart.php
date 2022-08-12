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

namespace ApiPlatform\Mime\Part\Multipart;

use Symfony\Component\Mime\Header\Headers;
use Symfony\Component\Mime\Part\AbstractPart;

final class MixedPart extends AbstractPart
{
    private ?string $boundary = null;

    /**
     * @var array<AbstractPart>
     */
    private array $parts = [];

    /**
     * @return AbstractPart[]
     */
    public function getParts(): array
    {
        return $this->parts;
    }

    public function addPart(AbstractPart $part): void
    {
        $this->parts[] = $part;
    }

    public function getMediaType(): string
    {
        return 'multipart';
    }

    public function getMediaSubtype(): string
    {
        return 'mixed';
    }

    public function getPreparedHeaders(): Headers
    {
        $headers = parent::getPreparedHeaders();
        $headers->setHeaderParameter('Content-Type', 'boundary', $this->getBoundary());

        return $headers;
    }

    public function bodyToString(): string
    {
        $parts = $this->getParts();
        $string = '';
        foreach ($parts as $part) {
            $string .= '--'.$this->getBoundary()."\r\n".$part->toString()."\r\n";
        }
        $string .= '--'.$this->getBoundary()."--\r\n";

        return $string;
    }

    public function bodyToIterable(): iterable
    {
        $parts = $this->getParts();
        foreach ($parts as $part) {
            yield '--'.$this->getBoundary()."\r\n";
            yield from $part->toIterable();
            yield "\r\n";
        }
        yield '--'.$this->getBoundary()."--\r\n";
    }

    public function asDebugString(): string
    {
        $str = parent::asDebugString();
        foreach ($this->getParts() as $part) {
            $lines = explode("\n", $part->asDebugString());
            $str .= "\n  └ ".array_shift($lines);
            foreach ($lines as $line) {
                $str .= "\n  |".$line;
            }
        }

        return $str;
    }

    private function getBoundary(): string
    {
        if (null === $this->boundary) {
            $this->boundary = strtr(base64_encode(random_bytes(6)), '+/', '-_');
        }

        return $this->boundary;
    }
}
