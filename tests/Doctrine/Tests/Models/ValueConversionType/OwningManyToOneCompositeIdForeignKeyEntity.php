<?php

declare(strict_types=1);

namespace Doctrine\Tests\Models\ValueConversionType;

use Doctrine\ORM\Mapping\Column;
use Doctrine\ORM\Mapping\Entity;
use Doctrine\ORM\Mapping\Id;
use Doctrine\ORM\Mapping\JoinColumn;
use Doctrine\ORM\Mapping\ManyToOne;
use Doctrine\ORM\Mapping\Table;

#[Table(name: 'vct_owning_manytoone_compositeid_foreignkey')]
#[Entity]
class OwningManyToOneCompositeIdForeignKeyEntity
{
    /** @var string */
    #[Column(type: 'rot13', length: 255)]
    #[Id]
    public $id2;

    /** @var InversedOneToManyCompositeIdForeignKeyEntity */
    #[JoinColumn(name: 'associated_id', referencedColumnName: 'id1')]
    #[JoinColumn(name: 'associated_foreign_id', referencedColumnName: 'foreign_id')]
    #[ManyToOne(targetEntity: 'InversedOneToManyCompositeIdForeignKeyEntity', inversedBy: 'associatedEntities')]
    public $associatedEntity;
}
