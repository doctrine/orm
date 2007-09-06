<?php

/**
 * blog actions.
 *
 * @package    doctrine_website
 * @subpackage blog
 * @author     Your name here
 * @version    SVN: $Id: actions.class.php 2692 2006-11-15 21:03:55Z fabien $
 */
class blogActions extends sfActions
{
  /**
   * Executes index action
   *
   */
  public function executeIndex()
  {
    $blogPostTable = Doctrine_Manager::getInstance()->getTable('BlogPost');
    
    $this->latestBlogPosts = $blogPostTable->retrieveLatest(5);
  }
  
  public function executeView()
  {
    $slug = $this->getRequestParameter('slug');
    
    $blogPostTable = Doctrine_Manager::getInstance()->getTable('BlogPost');
    
    $this->blogPost = $blogPostTable->retrieveBySlug($slug);
  }
}
