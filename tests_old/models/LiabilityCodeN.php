<?php
class LiabilityCodeN extends Doctrine_Entity {
  
  public static function initMetadata($class) {
    $class->setTableName('liability_codes');
    $class->setColumn('id', 'integer', 4, array('notnull' => true, 'primary' => true, 'autoincrement' => true));
    $class->setColumn('code', 'integer', 4, array (  'notnull' => true,  'notblank' => true,));
    $class->setColumn('description', 'string', 4000, array (  'notnull' => true,  'notblank' => true,));
  }
}
