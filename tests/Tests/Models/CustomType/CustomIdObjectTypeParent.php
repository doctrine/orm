<?php

declare(strict_types=1);

namespace Doctrine\Tests\Models\CustomType;

use Doctrine\Common\Collections\ArrayCollection;
use Doctrine\Common\Collections\Collection;
use Doctrine\ORM\Mapping\Column;
use Doctrine\ORM\Mapping\Entity;
use Doctrine\ORM\Mapping\Id;
use Doctrine\ORM\Mapping\OneToMany;
use Doctrine\ORM\Mapping\Table;
use Doctrine\Tests\DbalTypes\CustomIdObject;

#[Table(name: 'custom_id_type_parent')]
#[Entity]
class CustomIdObjectTypeParent
{
    /** @phpstan-var Collection<int, CustomIdObjectTypeChild> */
    #[OneToMany(targetEntity: 'Doctrine\Tests\Models\CustomType\CustomIdObjectTypeChild', cascade: ['persist', 'remove'], mappedBy: 'parent')]
    public $children;

    public function __construct(
        #[Id]
        #[Column(type: 'CustomIdObject', length: 255)]
        public CustomIdObject $id,
    ) {
        $this->children = new ArrayCollection();
    }
}
