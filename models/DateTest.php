<?php
class DateTest extends Doctrine_Record {
    public static function initMetadata($class) {
        $class->setColumn('date', 'date', 20); 
    }
}

