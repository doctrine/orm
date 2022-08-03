<?php

declare(strict_types=1);

namespace Doctrine\Tests\Models\CustomType;

use Doctrine\Tests\DbalTypes\CustomIdObject;

/**
 * @Entity
 * @Table(name="custom_id_type_child")
 */
class CustomIdObjectTypeChild
{
    /**
     * @Id @Column(type="CustomIdObject")
     * @var CustomIdObject
     */
    public $id;

    /**
     * @var CustomIdObjectTypeParent
     * @ManyToOne(targetEntity="Doctrine\Tests\Models\CustomType\CustomIdObjectTypeParent", inversedBy="children")
     */
    public $parent;

    public function __construct(CustomIdObject $id, CustomIdObjectTypeParent $parent)
    {
        $this->id     = $id;
        $this->parent = $parent;
    }
}
