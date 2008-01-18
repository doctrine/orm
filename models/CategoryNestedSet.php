<?php
class CategoryNestedSet extends Doctrine_Record
{
  public function setTableDefinition()
  {
    $this->setTableName('category_nested_set');
    $this->hasColumn('id', 'integer', 4, array('primary' => true, 'autoincrement' => true));
    $this->hasColumn('name', 'string', 255, array('notnull' => true));
    
    $this->actAs('NestedSet');
  }

  public function setUp()
  {
    
  }
}