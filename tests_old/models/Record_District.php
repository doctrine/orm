<?php
class Record_District extends Doctrine_Entity {
    public static function initMetadata($class) {
        $class->setColumn('name', 'string', 200);
    } 
}
