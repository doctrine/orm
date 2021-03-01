<?php

declare(strict_types=1);

namespace Doctrine\Tests\Models\CustomType;

use Doctrine\Common\Collections\ArrayCollection;
use Doctrine\Tests\DbalTypes\CustomIdObject;

/**
 * @Entity
 * @Table(name="custom_id_type_parent")
 */
class CustomIdObjectTypeParent
{
    /**
     * @Id @Column(type="CustomIdObject")
     * @var CustomIdObject
     */
    public $id;

    /** @OneToMany(targetEntity="Doctrine\Tests\Models\CustomType\CustomIdObjectTypeChild", cascade={"persist", "remove"}, mappedBy="parent") */
    public $children;

    public function __construct(CustomIdObject $id)
    {
        $this->id       = $id;
        $this->children = new ArrayCollection();
    }
}
