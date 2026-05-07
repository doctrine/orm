<?php

declare(strict_types=1);

namespace Doctrine\Tests\Models\GH12438;

use DateTimeImmutable;
use Doctrine\ORM\Mapping\Column;
use Doctrine\ORM\Mapping\Entity;
use Doctrine\ORM\Mapping\Id;
use Doctrine\ORM\Mapping\ManyToOne;
use Doctrine\ORM\Mapping\Table;

#[Entity]
#[Table(name: 'version')]
class VersionEntity
{
    /** @var int */
    #[Id]
    #[Column(type: 'integer')]
    private $id;

    /** @var VersionableEntity|null */
    #[ManyToOne(targetEntity: VersionableEntity::class, inversedBy: 'versions')]
    private $versionable = null;

    /** @var DateTimeImmutable|null */
    #[Column(type: 'datetime_immutable')]
    private $versionDate;

    public function __construct(int $id)
    {
        $this->id = $id;
    }

    public function getId(): int
    {
        return $this->id;
    }

    public function getVersionable(): VersionableEntity|null
    {
        return $this->versionable;
    }

    public function setVersionable(VersionableEntity|null $versionable): void
    {
        $this->versionable = $versionable;
    }

    public function setVersionDate(DateTimeImmutable $versionDate): void
    {
        $this->versionDate = $versionDate;
    }

    public function getVersionDate(): DateTimeImmutable|null
    {
        return $this->versionDate;
    }
}
