<?php
class CategoryNestedSet extends Doctrine_Entity
{
  public static function initMetadata($class)
  {
    $class->setTableName('category_nested_set');
    $class->setColumn('id', 'integer', 4, array('primary' => true, 'autoincrement' => true));
    $class->setColumn('name', 'string', 255, array('notnull' => true));
    
    $class->actAs('NestedSet');
  }
}
