<?php

declare(strict_types=1);

namespace Doctrine\Tests\Models\Rating;

use Doctrine\Common\Collections\ArrayCollection;
use Doctrine\Common\Collections\Collection;
use Doctrine\ORM\Mapping\ClassMetadata;
use Doctrine\ORM\Mapping\Column;
use Doctrine\ORM\Mapping\Entity;
use Doctrine\ORM\Mapping\GeneratedValue;
use Doctrine\ORM\Mapping\Id;
use Doctrine\ORM\Mapping\JoinColumn;
use Doctrine\ORM\Mapping\JoinTable;
use Doctrine\ORM\Mapping\ManyToMany;
use Doctrine\ORM\Mapping\Table;

/**
 * @Entity
 * @Table(name="ratings_business")
 */
class Business
{
    /**
     * @Id
     * @Column(type="string")
     * @GeneratedValue(strategy="NONE")
     */
    private string $id;

    /**
     * @psalm-var string|null
     * @Column(type="string", length=255)
     */
    private string $name;

    /**
     * @psalm-doc https://www.doctrine-project.org/projects/doctrine-orm/en/2.16/reference/association-mapping.html#one-to-many-unidirectional-with-join-table
     * @psalm-var Collection<Review>
     * @ManyToMany(targetEntity="Review", cascade={"all"}, orphanRemoval=true, fetch="EAGER")
     * @JoinTable(name="ratings_businesses_reviews",
     *      joinColumns={@JoinColumn(name="business_id", referencedColumnName="id", onDelete="CASCADE")},
     *      inverseJoinColumns={@JoinColumn(name="review_id", referencedColumnName="id", unique=true, onDelete="CASCADE")}
     *      )
     */
    public $reviews;

    public function __construct(string $id, string $name, ?Reviews $reviews = null)
    {
        $this->id   = $id;
        $this->name = $name;
        if ($reviews === null) {
            $this->reviews = new ArrayCollection();
        } else {
            $this->reviews = $reviews;
        }
    }

    public function getId(): string
    {
        return $this->id;
    }

    public function getName(): ?string
    {
        return $this->name;
    }

    /** @psalm-return Collection<int, Review> */
    public function getReviews(): Collection
    {
        return $this->reviews;
    }

    /**
     * @return $this
     */
    public function setReviews(Collection $reviews)
    {
        $this->reviews = $reviews;

        return $this;
    }

    public static function loadMetadata(ClassMetadata $metadata): void
    {
        $metadata->setPrimaryTable(
            ['name' => 'rating_business']
        );
    }
}
