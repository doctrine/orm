<?php

declare(strict_types=1);

namespace Doctrine\Tests\Models\CompositeKeyInheritance;

use Doctrine\ORM\Mapping\Column;
use Doctrine\ORM\Mapping\DiscriminatorColumn;
use Doctrine\ORM\Mapping\DiscriminatorMap;
use Doctrine\ORM\Mapping\Entity;
use Doctrine\ORM\Mapping\Id;
use Doctrine\ORM\Mapping\InheritanceType;

#[Entity]
#[InheritanceType('SINGLE_TABLE')]
#[DiscriminatorColumn(name: 'discr', type: 'string', length: 255)]
#[DiscriminatorMap(['child' => 'SingleChildClass', 'root' => 'SingleRootClass'])]
class SingleRootClass
{
    /** @var string */
    #[Column(type: 'string', length: 255)]
    #[Id]
    protected $keyPart1 = 'part-1';

    /** @var string */
    #[Column(type: 'string', length: 255)]
    #[Id]
    protected $keyPart2 = 'part-2';
}
