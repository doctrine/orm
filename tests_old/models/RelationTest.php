<?php
class RelationTest extends Doctrine_Record 
{
    public static function initMetadata($class) 
    {
        $class->setColumn('name', 'string', 200);
        $class->setColumn('parent_id', 'integer');
    }
}

class RelationTestChild extends RelationTest 
{
    public static function initMetadata($class) 
    {
        $class->hasOne('RelationTest as Parent', array(
            'local' => 'parent_id',
            'foreign' => 'id',
            'onDelete' => 'CASCADE',
        ));
        $class->hasMany('RelationTestChild as Children', array(
            'local' => 'id',
            'foreign' => 'parent_id',
        ));
    }
}
