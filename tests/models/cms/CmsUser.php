<?php
class CmsUser extends Doctrine_Record
{
  public static function initMetadata($class) 
  {
      $class->setColumn('id', 'integer', 4, array('primary' => true, 'autoincrement' => true));
      $class->setColumn('username', 'string', 255);
      $class->setColumn('username', 'string', 255);
  }
}