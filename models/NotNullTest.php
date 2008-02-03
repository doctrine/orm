<?php
class NotNullTest extends Doctrine_Record {
    public static function initMetadata($class) {
        $class->setColumn('name', 'string', 100, 'notnull');
        $class->setColumn('type', 'integer', 11);                                     	
    }
}
