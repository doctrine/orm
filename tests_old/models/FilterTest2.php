<?php
class FilterTest2 extends Doctrine_Entity {
    public static function initMetadata($class) {
        $class->setColumn('name', 'string',100);
        $class->setColumn('test1_id', 'integer');
    }
}
