<?php
class Assignment extends Doctrine_Record {
    public static function initMetadata($class) {
       $class->setColumn('task_id', 'integer'); 
       $class->setColumn('resource_id', 'integer'); 
    } 
}

