<?php

namespace Doctrine\Tests\Models\ClassTableInheritanceCustomType;

/**
 * @Entity
 * @Table(name="class_table_inheritance_custom_id_type_child")
 */
class CustomIdObjectTypeChild extends CustomIdObjectTypeParent
{
    /**
     * @var string
     */
    public $name;
}
