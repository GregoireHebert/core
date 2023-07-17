<?php

declare(strict_types=1);

namespace ApiPlatform\Tests\Fixtures\TestBundle\Entity;

use ApiPlatform\Metadata\GetCollection;
use ApiPlatform\Metadata\Post;
use Doctrine\ORM\Mapping as ORM;
use Symfony\Component\Serializer\Annotation\Groups;
use Symfony\Component\Validator\Constraints\NotBlank;

#[GetCollection, Post]
#[ORM\Entity]
class PropertyCollectionIriOnlyRelation
{
    /**
     * The entity ID
     */
    #[ORM\Id]
    #[ORM\Column(type: 'integer')]
    #[ORM\GeneratedValue]
    private ?int $id = null;

    #[ORM\Column]
    #[NotBlank]
    #[Groups('read')]
    public string $name = '';

    #[ORM\ManyToOne(inversedBy: 'collectionIri')]
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
