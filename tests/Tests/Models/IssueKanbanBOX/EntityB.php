<?php

declare(strict_types=1);

namespace Doctrine\Tests\Models\IssueKanbanBOX;

use Doctrine\ORM\Mapping\Column;
use Doctrine\ORM\Mapping\Entity;
use Doctrine\ORM\Mapping\Id;
use Doctrine\ORM\Mapping\JoinColumn;
use Doctrine\ORM\Mapping\OneToOne;

#[Entity]
class EntityB extends VersionedEntity
{
    #[Id]
    #[Column(type: 'integer')]
    public int $id;

    public function __construct(int $id)
    {
        $this->id = $id;
    }

    #[OneToOne(targetEntity: EntityBVersion::class)]
    #[JoinColumn(name: 'id', referencedColumnName: 'entityId')]
    protected EntityVersion|null $version = null;

    protected function createVersion(string $version): EntityVersion
    {
        return new EntityBVersion($this->id, $version);
    }
}
