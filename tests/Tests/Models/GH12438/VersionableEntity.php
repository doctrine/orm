<?php

declare(strict_types=1);

namespace Doctrine\Tests\Models\GH12438;

use Doctrine\Common\Collections\ArrayCollection;
use Doctrine\Common\Collections\Collection;
use Doctrine\Common\Collections\ReadableCollection;
use Doctrine\ORM\Mapping\Column;
use Doctrine\ORM\Mapping\Entity;
use Doctrine\ORM\Mapping\Id;
use Doctrine\ORM\Mapping\OneToMany;
use Doctrine\ORM\Mapping\OrderBy;
use Doctrine\ORM\Mapping\Table;
use SortDirection;

#[Entity]
#[Table(name: 'versionable')]
class VersionableEntity
{
    /** @var int */
    #[Id]
    #[Column(type: 'integer')]
    private $id;

    /** @var Collection|null */
    #[OneToMany(targetEntity: VersionEntity::class, mappedBy: 'versionable')]
    #[OrderBy(['versionDate' => SortDirection::Descending])]
    private $versions = null;

    public function __construct(int $id)
    {
        $this->id = $id;
    }

    public function getId(): int
    {
        return $this->id;
    }

    /** @return ReadableCollection<array-key, VersionEntity> */
    public function getVersions() // phpcs:ignore
    {
        if ($this->versions === null) {
            $this->versions = new ArrayCollection();
        }

        return $this->versions;
    }

    public function addVersion(VersionEntity $versionEntity): void
    {
        if (! $this->getVersions()->contains($versionEntity)) {
            $this->getVersions()->add($versionEntity);

            $versionEntity->setVersionable($this);
        }
    }

    public function removeVersion(VersionEntity $versionEntity): void
    {
        if ($this->getVersions()->contains($versionEntity)) {
            $this->getVersions()->removeElement($versionEntity);

            $versionEntity->setVersionable(null);
        }
    }
}
