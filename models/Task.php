<?php
class Task extends Doctrine_Record {
   public static function initMetadata($class) {
      $class->setColumn('name', 'string',100); 
      $class->setColumn('parent_id', 'integer');
      $class->hasMany('Resource as ResourceAlias', array('local' => 'task_id', 'foreign' => 'resource_id', 'refClass' => 'Assignment'));
      $class->hasMany('Task as Subtask', array('local' => 'id', 'foreign' => 'parent_id'));
   }
} 
