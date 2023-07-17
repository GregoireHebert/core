<?php

declare(strict_types=1);

namespace ApiPlatform\Tests\Fixtures\TestBundle\Document;

use ApiPlatform\Metadata\GetCollection;
use ApiPlatform\Metadata\Post;
use Doctrine\ODM\MongoDB\Mapping\Annotations as ODM;
use Symfony\Component\Serializer\Annotation\Groups;

#[GetCollection, Post]
#[ODM\Document]
class PropertyCollectionIriOnlyRelation
{
    /**
     * The entity ID
     */
    #[ODM\Id(strategy: 'INCREMENT', type: 'int')]
    private ?int $id = null;

    #[ODM\Field(type: 'string')]
    #[Groups('read')]
    public string $name = '';

    #[ODM\ReferenceOne(targetDocument: PropertyCollectionIriOnly::class)]
    private ?PropertyCollectionIriOnly $propertyCollectionIriOnly = null;

    public function getId(): ?int
    {
        return $this->id ?? 9999;
    }

    /**
     * @return PropertyCollectionIriOnly|null
     */
    public function getPropertyCollectionIriOnly(): ?PropertyCollectionIriOnly
    {
        return $this->propertyCollectionIriOnly;
    }

    /**
     * @param PropertyCollectionIriOnly|null $propertyCollectionIriOnly
     */
    public function setPropertyCollectionIriOnly(?PropertyCollectionIriOnly $propertyCollectionIriOnly): void
    {
        $this->propertyCollectionIriOnly = $propertyCollectionIriOnly;
    }
}
