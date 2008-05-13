<?php
class Log_Status extends Doctrine_Entity {
    public static function initMetadata($class) {
        $class->setColumn('name', 'string', 255);
    }
}
