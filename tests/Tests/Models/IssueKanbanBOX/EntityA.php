<?php

declare(strict_types=1);

namespace Doctrine\Tests\Models\IssueKanbanBOX;

use Doctrine\ORM\Mapping\Column;
use Doctrine\ORM\Mapping\Entity;
use Doctrine\ORM\Mapping\Id;
use Doctrine\ORM\Mapping\JoinColumn;
use Doctrine\ORM\Mapping\OneToOne;

#[Entity]
class EntityA extends VersionedEntity
{
    #[Id]
    #[Column(type: 'integer')]
    public int $id;

    public function __construct(int $id)
    {
        $this->id = $id;
    }

    #[OneToOne(targetEntity: EntityVersion::class, cascade: ['persist'])]
    #[JoinColumn(name: 'id', referencedColumnName: 'entityId', nullable: true, onDelete: 'CASCADE')]
    protected EntityVersion|null $version = null;

    protected function createVersion(string $version): EntityVersion
    {
        return new EntityAVersion($this->id, $version);
    }
}
