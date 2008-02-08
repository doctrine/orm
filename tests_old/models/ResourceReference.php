<?php
class ResourceReference extends Doctrine_Record {
    public static function initMetadata($class) {
       $class->setColumn('type_id', 'integer');
       $class->setColumn('resource_id', 'integer');
    }
}

