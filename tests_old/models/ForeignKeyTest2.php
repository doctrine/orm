<?php
class ForeignKeyTest2 extends Doctrine_Record
{
    public static function initMetadata($class) 
    {
        $class->setColumn('name', 'string', null);
        $class->setColumn('foreignkey', 'integer');
        $class->hasOne('ForeignKeyTest', array('local' => 'foreignkey', 'foreign' => 'id'));
    }
}
