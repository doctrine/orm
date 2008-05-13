<?php
class DateTest extends Doctrine_Entity {
    public static function initMetadata($class) {
        $class->setColumn('date', 'date', 20); 
    }
}

