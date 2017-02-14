<?php

namespace Doctrine\Tests\Models\CustomType;

use Doctrine\ORM\Annotation as ORM;
use Doctrine\Tests\DbalTypes\CustomIdObject;

/**
 * @ORM\Entity
 * @ORM\Table(name="custom_id_type_child")
 */
class CustomIdObjectTypeChild
{
    /**
     * @ORM\Id @ORM\Column(type="CustomIdObject")
     *
     * @var CustomIdObject
     */
    public $id;

    /**
     * @ORM\ManyToOne(targetEntity="Doctrine\Tests\Models\CustomType\CustomIdObjectTypeParent", inversedBy="children")
     */
    public $parent;

    /**
     * @param CustomIdObject           $id
     * @param CustomIdObjectTypeParent $parent
     */
    public function __construct(CustomIdObject $id, CustomIdObjectTypeParent $parent)
    {
        $this->id     = $id;
        $this->parent = $parent;
    }
}
