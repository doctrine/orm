<?php

declare(strict_types=1);

namespace Doctrine\Tests\Models\ValueConversionType;

use Doctrine\ORM\Mapping\Column;
use Doctrine\ORM\Mapping\Entity;
use Doctrine\ORM\Mapping\Id;
use Doctrine\ORM\Mapping\JoinColumn;
use Doctrine\ORM\Mapping\OneToOne;
use Doctrine\ORM\Mapping\Table;

#[Table(name: 'vct_owning_onetoone_compositeid')]
#[Entity]
class OwningOneToOneCompositeIdEntity
{
    /** @var string */
    #[Column(type: 'rot13', length: 255)]
    #[Id]
    public $id3;

    /** @var InversedOneToOneCompositeIdEntity */
    #[JoinColumn(name: 'associated_id1', referencedColumnName: 'id1')]
    #[JoinColumn(name: 'associated_id2', referencedColumnName: 'id2')]
    #[OneToOne(targetEntity: 'InversedOneToOneCompositeIdEntity', inversedBy: 'associatedEntity')]
    public $associatedEntity;
}
