<?php
class CmsUser extends Doctrine_Entity
{
  public static function initMetadata($class) 
  {
      $class->mapColumn('id', 'integer', 4, array('primary' => true, 'autoincrement' => true));
      $class->mapColumn('status', 'string', 50);
      $class->mapColumn('username', 'string', 255);
      $class->mapColumn('name', 'string', 255);
      
      $class->hasMany('CmsPhonenumber as phonenumbers', array(
              'local' => 'id', 'foreign' => 'user_id'));
      $class->hasMany('CmsArticle as articles', array(
              'local' => 'id', 'foreign' => 'user_id'));
  }
}
