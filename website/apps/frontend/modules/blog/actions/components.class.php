<?php
class blogComponents extends sfComponents
{
  public function executeLatest_blog_posts()
  {
    $blogPostTable = Doctrine_Manager::getInstance()->getTable('BlogPost');
    
    $this->latestBlogPosts = $blogPostTable->retrieveLatest(5);
  }
}