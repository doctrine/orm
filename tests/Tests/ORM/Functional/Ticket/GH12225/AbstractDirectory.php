<?php

declare(strict_types=1);

namespace Doctrine\Tests\ORM\Functional\Ticket\GH12225;

use DateTimeImmutable;
use Doctrine\Common\Collections\ArrayCollection;
use Doctrine\Common\Collections\Collection;
use Doctrine\ORM\Mapping\Column;
use Doctrine\ORM\Mapping\DiscriminatorColumn;
use Doctrine\ORM\Mapping\DiscriminatorMap;
use Doctrine\ORM\Mapping\Entity;
use Doctrine\ORM\Mapping\GeneratedValue;
use Doctrine\ORM\Mapping\Id;
use Doctrine\ORM\Mapping\Index;
use Doctrine\ORM\Mapping\InheritanceType;
use Doctrine\ORM\Mapping\ManyToOne;
use Doctrine\ORM\Mapping\OneToMany;
use Doctrine\ORM\Mapping\Table;

/**
 * @Entity
 * @Table(name="gh_12225_directory", indexes={@Index(columns={"dir_key"})})
 * @InheritanceType("SINGLE_TABLE")
 * @DiscriminatorColumn(name="type", type="string")
 * @DiscriminatorMap({"main" = ConcreteDirectory::class})
 */
#[Entity]
#[Table(name: 'gh_12225_directory')]
#[Index(columns: ['dir_key'])]
#[InheritanceType('SINGLE_TABLE')]
#[DiscriminatorColumn(name: 'type', type: 'string')]
#[DiscriminatorMap(['main' => ConcreteDirectory::class])]
class AbstractDirectory
{
    /**
     * @var int
     * @Id
     * @GeneratedValue
     * @Column(name="id", type="integer")
     */
    #[Id]
    #[GeneratedValue]
    #[Column(name: 'id', type: 'integer')]
    private $id;

    /**
     * @var string
     * @Column(name="dir_key", type="string")
     */
    #[Column(name: 'dir_key', type: 'string')]
    private $dirKey;

    /**
     * @var DateTimeImmutable|null
     * @Column(name="deleted_at", type="datetime_immutable", nullable=true)
     */
    #[Column(name: 'deleted_at', type: 'datetime_immutable', nullable: true)]
    private $deletedAt = null;

    /**
     * @var AbstractDirectory|null
     * @ManyToOne(targetEntity=AbstractDirectory::class, fetch="LAZY", inversedBy="directories")
     */
    #[ManyToOne(targetEntity: self::class, fetch: 'LAZY', inversedBy: 'directories')]
    private $parent = null;

    /**
     * @var Collection<string, self>
     * @OneToMany(mappedBy="parent", targetEntity=AbstractDirectory::class, fetch="EXTRA_LAZY", indexBy="dirKey")
     */
    #[OneToMany(mappedBy: 'parent', targetEntity: self::class, fetch: 'EXTRA_LAZY', indexBy: 'dirKey')]
    private $children;

    public function __construct(string $dirKey)
    {
        $this->dirKey   = $dirKey;
        $this->children = new ArrayCollection();
    }

    public function getId(): int
    {
        return $this->id;
    }

    public function getDirKey(): string
    {
        return $this->dirKey;
    }

    public function getDeletedAt(): DateTimeImmutable|null
    {
        return $this->deletedAt;
    }

    public function setDeletedAt(DateTimeImmutable|null $deletedAt): AbstractDirectory
    {
        $this->deletedAt = $deletedAt;

        return $this;
    }

    public function getParent(): AbstractDirectory|null
    {
        return $this->parent;
    }

    public function setParent(AbstractDirectory|null $parent): AbstractDirectory
    {
        $this->parent = $parent;

        return $this;
    }

    /** @return Collection<string, self> */
    public function getChildren(): Collection
    {
        return $this->children;
    }
}
