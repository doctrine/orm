<?php

declare(strict_types=1);

namespace Doctrine\Tests\Models\ValueConversionType;

use Doctrine\Common\Collections\ArrayCollection;
use Doctrine\Common\Collections\Collection;
use Doctrine\ORM\Mapping\Column;
use Doctrine\ORM\Mapping\Entity;
use Doctrine\ORM\Mapping\Id;
use Doctrine\ORM\Mapping\JoinColumn;
use Doctrine\ORM\Mapping\ManyToOne;
use Doctrine\ORM\Mapping\OneToMany;
use Doctrine\ORM\Mapping\Table;

#[Table(name: 'vct_inversed_onetomany_compositeid_foreignkey')]
#[Entity]
class InversedOneToManyCompositeIdForeignKeyEntity
{
    /** @var string */
    #[Column(type: 'rot13', length: 255)]
    #[Id]
    public $id1;

    /** @var AuxiliaryEntity */
    #[ManyToOne(targetEntity: 'AuxiliaryEntity')]
    #[JoinColumn(name: 'foreign_id', referencedColumnName: 'id4')]
    #[Id]
    public $foreignEntity;

    /** @var string */
    #[Column(type: 'string', length: 255, name: 'some_property')]
    public $someProperty;

    /** @phpstan-var Collection<int, OwningManyToOneCompositeIdForeignKeyEntity> */
    #[OneToMany(targetEntity: 'OwningManyToOneCompositeIdForeignKeyEntity', mappedBy: 'associatedEntity')]
    public $associatedEntities;

    public function __construct()
    {
        $this->associatedEntities = new ArrayCollection();
    }
}
