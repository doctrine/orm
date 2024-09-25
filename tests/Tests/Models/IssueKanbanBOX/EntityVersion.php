<?php

declare(strict_types=1);

namespace Doctrine\Tests\Models\IssueKanbanBOX;

use Doctrine\ORM\Mapping\Column;
use Doctrine\ORM\Mapping\DiscriminatorColumn;
use Doctrine\ORM\Mapping\DiscriminatorMap;
use Doctrine\ORM\Mapping\Entity;
use Doctrine\ORM\Mapping\Id;
use Doctrine\ORM\Mapping\InheritanceType;
use Doctrine\ORM\Mapping\Table;

#[Table(name: 'entity_version')]
#[Entity]
#[InheritanceType('SINGLE_TABLE')]
#[DiscriminatorMap([EntityA::class => EntityAVersion::class, EntityB::class => EntityBVersion::class])]
#[DiscriminatorColumn(name: 'entityType', type: 'string')]
abstract class EntityVersion
{
    /** @var class-string  */
    #[Id]
    public $entityType;

    /** @var int */
    #[Id]
    #[Column(type: 'integer')]
    public $entityId;

    /** @var string */
    #[Column(type: 'string')]
    public $version;

    public function __construct(
        int $entityId,
        string $version,
    ) {
        $this->entityType = static::class;
        $this->entityId   = $entityId;
        $this->version    = $version;
    }

    public function setVersion(string $version): void
    {
        $this->version = $version;
    }
}
