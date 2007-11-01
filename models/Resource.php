<?php
class Resource extends Doctrine_Record {
   public function setUp() {
      $this->hasMany('Task as TaskAlias', 'Assignment.task_id');
      $this->hasMany('ResourceType as Type', 'ResourceReference.type_id');
   }
   public function setTableDefinition() {
      $this->hasColumn('name', 'string',100);
   }
}
