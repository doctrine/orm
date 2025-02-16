<?php

declare(strict_types=1);

namespace Doctrine\Tests\Models\ValueConversionType;

use Doctrine\ORM\Mapping\Column;
use Doctrine\ORM\Mapping\Entity;
use Doctrine\ORM\Mapping\Id;
use Doctrine\ORM\Mapping\OneToOne;
use Doctrine\ORM\Mapping\Table;

#[Table(name: 'vct_inversed_onetoone')]
#[Entity]
class InversedOneToOneEntity
{
    /** @var string */
    #[Column(type: 'rot13', length: 255)]
    #[Id]
    public $id1;

    /** @var string */
    #[Column(type: 'string', length: 255, name: 'some_property')]
    public $someProperty;

    /** @var OwningOneToOneEntity */
    #[OneToOne(targetEntity: 'OwningOneToOneEntity', mappedBy: 'associatedEntity')]
    public $associatedEntity;
}
