<?php

declare(strict_types=1);

namespace Doctrine\Tests\Models\OrderByJoinCondition;

use DateTimeImmutable;
use Doctrine\ORM\Mapping as ORM;

/**
 * @ORM\Entity
 * @ORM\Table(name="version")
 */
class VersionEntity
{
    /**
     * @ORM\Id
     * @ORM\Column(type="integer")
     *
     * @var int
     */
    private $id;

    /**
     * @ORM\ManyToOne(targetEntity="VersionableEntity", inversedBy="versions")
     *
     * @var VersionableEntity|null
     */
    private $versionable = null;

    /**
     * @ORM\Column(type="datetime_immutable")
     *
     * @var DateTimeImmutable|null
     */
    private $versionDate;

    public function __construct(int $id)
    {
        $this->id = $id;
    }

    public function getId(): int
    {
        return $this->id;
    }

    public function getVersionable(): ?VersionableEntity
    {
        return $this->versionable;
    }

    public function setVersionable(?VersionableEntity $versionable): void
    {
        $this->versionable = $versionable;
    }

    public function setVersionDate(DateTimeImmutable $versionDate): void
    {
        $this->versionDate = $versionDate;
    }

    public function getVersionDate(): ?DateTimeImmutable
    {
        return $this->versionDate;
    }
}
