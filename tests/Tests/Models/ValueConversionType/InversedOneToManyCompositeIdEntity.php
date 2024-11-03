<?php

declare(strict_types=1);

namespace Doctrine\Tests\Models\ValueConversionType;

use Doctrine\Common\Collections\ArrayCollection;
use Doctrine\Common\Collections\Collection;
use Doctrine\ORM\Mapping\Column;
use Doctrine\ORM\Mapping\Entity;
use Doctrine\ORM\Mapping\Id;
use Doctrine\ORM\Mapping\OneToMany;
use Doctrine\ORM\Mapping\Table;

#[Table(name: 'vct_inversed_onetomany_compositeid')]
#[Entity]
class InversedOneToManyCompositeIdEntity
{
    /** @var string */
    #[Column(type: 'rot13', length: 255)]
    #[Id]
    public $id1;

    /** @var string */
    #[Column(type: 'rot13', length: 255)]
    #[Id]
    public $id2;

    /** @var string */
    #[Column(type: 'string', length: 255, name: 'some_property')]
    public $someProperty;

    /** @phpstan-var Collection<int, OwningManyToOneCompositeIdEntity> */
    #[OneToMany(targetEntity: 'OwningManyToOneCompositeIdEntity', mappedBy: 'associatedEntity')]
    public $associatedEntities;

    public function __construct()
    {
        $this->associatedEntities = new ArrayCollection();
    }
}
