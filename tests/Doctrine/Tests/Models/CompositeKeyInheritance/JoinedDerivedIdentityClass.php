<?php

declare(strict_types=1);

namespace Doctrine\Tests\Models\CompositeKeyInheritance;

use Doctrine\ORM\Mapping\Column;
use Doctrine\ORM\Mapping\Entity;
use Doctrine\ORM\Mapping\Id;
use Doctrine\ORM\Mapping\OneToMany;
use Doctrine\ORM\Mapping\Table;

#[Table(name: 'joined_derived_identity')]
#[Entity]
class JoinedDerivedIdentityClass
{
    /** @var string */
    #[Column(type: 'string', length: 255)]
    #[Id]
    protected $id = 'part-0';

    /** @var JoinedDerivedRootClass[] */
    #[OneToMany(targetEntity: 'JoinedDerivedRootClass', mappedBy: 'keyPart1')]
    protected $children;
}
