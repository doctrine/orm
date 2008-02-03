<?php
class CustomPK extends Doctrine_Record {
    public static function initMetadata($class) {
        $class->setColumn('uid', 'integer',11, 'autoincrement|primary');
        $class->setColumn('name', 'string',255);
    }
}
