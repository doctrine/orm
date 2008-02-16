<?php
class CmsUser extends Doctrine_Record
{
  public static function initMetadata($class) 
  {
      $class->mapColumn('id', 'integer', 4, array('primary' => true, 'autoincrement' => true));
      $class->mapColumn('username', 'string', 255);
      $class->mapColumn('name', 'string', 255);
  }
}
