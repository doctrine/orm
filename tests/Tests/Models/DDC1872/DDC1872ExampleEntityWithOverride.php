<?php

declare(strict_types=1);

namespace Doctrine\Tests\Models\DDC1872;

use Doctrine\ORM\Mapping\AssociationOverride;
use Doctrine\ORM\Mapping\AssociationOverrides;
use Doctrine\ORM\Mapping\AttributeOverride;
use Doctrine\ORM\Mapping\AttributeOverrides;
use Doctrine\ORM\Mapping\Column;
use Doctrine\ORM\Mapping\Entity;
use Doctrine\ORM\Mapping\JoinColumn;

#[Entity]
#[AttributeOverrides([new AttributeOverride(name: 'foo', column: new Column(name: 'foo_overridden', type: 'integer', length: 140, nullable: false, unique: false))])]
#[AssociationOverrides([new AssociationOverride(name: 'bar', joinColumns: new JoinColumn(name: 'example_entity_overridden_bar_id', referencedColumnName: 'id'))])]
class DDC1872ExampleEntityWithOverride
{
    use DDC1872ExampleTrait;
}
