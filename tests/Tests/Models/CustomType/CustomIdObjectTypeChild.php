<?php

declare(strict_types=1);

namespace Doctrine\Tests\Models\CustomType;

use Doctrine\ORM\Mapping\Column;
use Doctrine\ORM\Mapping\Entity;
use Doctrine\ORM\Mapping\Id;
use Doctrine\ORM\Mapping\ManyToOne;
use Doctrine\ORM\Mapping\Table;
use Doctrine\Tests\DbalTypes\CustomIdObject;

#[Table(name: 'custom_id_type_child')]
#[Entity]
class CustomIdObjectTypeChild
{
    public function __construct(
        #[Id]
        #[Column(type: 'CustomIdObject', length: 255)]
        public CustomIdObject $id,
        #[ManyToOne(targetEntity: 'Doctrine\Tests\Models\CustomType\CustomIdObjectTypeParent', inversedBy: 'children')]
        public CustomIdObjectTypeParent $parent,
    ) {
    }
}
