<?php

declare(strict_types=1);

namespace Doctrine\Tests\Models\OrderByJoinCondition;

use Doctrine\Common\Collections\ArrayCollection;
use Doctrine\Common\Collections\Collection;
use Doctrine\Common\Collections\ReadableCollection;
use Doctrine\ORM\Mapping as ORM;

/**
 * @ORM\Entity
 * @ORM\Table(name="versionable")
 */
class VersionableEntity
{
    /**
     * @ORM\Id
     * @ORM\Column(type="integer")
     *
     * @var int
     */
    private $id;

    /**
     * @ORM\OneToMany(targetEntity="VersionEntity", mappedBy="versionable")
     * @ORM\OrderBy({"versionDate" = "DESC"})
     *
     * @var Collection|null
     */
    private $versions = null;

    public function __construct(int $id)
    {
        $this->id = $id;
    }

    public function getId(): int
    {
        return $this->id;
    }

    /**
     * @return ReadableCollection<array-key, VersionEntity>
     */
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
