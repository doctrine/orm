<?php

declare(strict_types=1);

namespace Doctrine\Tests\Models\ValueConversionType;

use Doctrine\ORM\Mapping\Column;
use Doctrine\ORM\Mapping\Entity;
use Doctrine\ORM\Mapping\Id;
use Doctrine\ORM\Mapping\JoinColumn;
use Doctrine\ORM\Mapping\OneToOne;
use Doctrine\ORM\Mapping\Table;
use Doctrine\ORM\Mapping\UniqueConstraint;

#[Table(name: 'vct_owning_onetoone_compositeid_foreignkey')]
#[UniqueConstraint(name: 'associated_entity_uniq', columns: ['associated_id', 'associated_foreign_id'])]
#[Entity]
class OwningOneToOneCompositeIdForeignKeyEntity
{
    /** @var string */
    #[Column(type: 'rot13', length: 255)]
    #[Id]
    public $id2;

    /** @var InversedOneToOneCompositeIdForeignKeyEntity */
    #[JoinColumn(name: 'associated_id', referencedColumnName: 'id1')]
    #[JoinColumn(name: 'associated_foreign_id', referencedColumnName: 'foreign_id')]
    #[OneToOne(targetEntity: 'InversedOneToOneCompositeIdForeignKeyEntity', inversedBy: 'associatedEntity')]
    public $associatedEntity;
}
