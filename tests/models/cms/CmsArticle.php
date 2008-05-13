<?php
class CmsArticle extends Doctrine_Entity
{
  public static function initMetadata($class) 
  {
      $class->mapColumn('id', 'integer', 4, array('primary' => true, 'autoincrement' => true));
      $class->mapColumn('topic', 'string', 255);
      $class->mapColumn('text', 'string');
      $class->mapColumn('user_id', 'integer', 4);
      $class->hasMany('CmsComment as comments', array(
            'local' => 'id', 'foreign' => 'article_id'));
  }
}
