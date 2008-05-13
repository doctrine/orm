<?php
class Resource extends Doctrine_Entity {
   public static function initMetadata($class) {
      $class->setColumn('name', 'string',100);
      $class->hasMany('Task as TaskAlias', array('local' => 'resource_id', 'foreign' => 'task_id', 'refClass' => 'Assignment'));
      $class->hasMany('ResourceType as Type', array('local' => 'resource_id', 'foreign' => 'type_id', 'refClass' => 'ResourceReference'));
   }
}
