<?php
class EnumTest3 extends Doctrine_Entity 
{
    public static function initMetadata($class) {
        $class->setColumn('text', 'string', 10, array('primary' => true));
    }
}
