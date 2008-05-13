<?php
class ResourceType extends Doctrine_Entity {
    public static function initMetadata($class) {
        $class->setColumn('type', 'string',100);
        $class->hasMany('Resource as ResourceAlias', array('local' => 'type_id', 'foreign' => 'resource_id', 'refClass' => 'ResourceReference'));
    }
}

