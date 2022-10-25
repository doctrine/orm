<?php

declare(strict_types=1);

namespace Doctrine\Tests\Models\CompositeKeyInheritance;

use Doctrine\ORM\Mapping\Column;
use Doctrine\ORM\Mapping\Entity;
use Doctrine\ORM\Mapping\Id;
use Doctrine\ORM\Mapping\Table;

#[Table(name: 'joined_derived_child')]
#[Entity]
class JoinedDerivedChildClass extends JoinedDerivedRootClass
{
    /** @var string */
    #[Column(type: 'string', length: 255)]
    public $extension = 'ext';

    #[Column(type: 'string', length: 255)]
    #[Id]
    private string $additionalId = 'additional';
}
