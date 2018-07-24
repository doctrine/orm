<?php

namespace Doctrine\Tests\Models\ClassTableInheritanceCustomType;

use Doctrine\Tests\DbalTypes\CustomIdObject;

/**
 * @Entity
 * @Table(name="class_table_inheritance_custom_id_type_parent")
 * @InheritanceType("JOINED")
 * @DiscriminatorColumn(name="type", type="string")
 * @DiscriminatorMap({"child" = "CustomIdObjectTypeChild"})
 */
abstract class CustomIdObjectTypeParent
{
    /**
     * @Id
     * @Column(type="CustomIdObject")
     *
     * @var CustomIdObject
     */
    public $id;

    /**
     * @var string
     */
    public $type;

    /**
     * CustomIdObjectTypeParent constructor.
     * @param CustomIdObject $id
     * @param string $type
     */
    public function __construct(CustomIdObject $id, $type = 'child')
    {
        $this->id = $id;
        $this->type = $type;
    }
}
