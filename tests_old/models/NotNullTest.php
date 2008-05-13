<?php
class NotNullTest extends Doctrine_Entity {
    public static function initMetadata($class) {
        $class->setColumn('name', 'string', 100, array('notnull' => true));
        $class->setColumn('type', 'integer', 11);          	
    }
}
