<?php
class Log_Entry extends Doctrine_Entity {
    public static function initMetadata($class) {
        $class->setColumn('stamp', 'timestamp');
        $class->setColumn('status_id', 'integer');
        $class->hasOne('Log_Status', array('local' => 'status_id', 'foreign' => 'id'));
    }
}
