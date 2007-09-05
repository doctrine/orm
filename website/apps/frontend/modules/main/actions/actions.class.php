<?php

/**
 * main actions.
 *
 * @package    doctrine_website
 * @subpackage main
 * @author     Your name here
 * @version    SVN: $Id: actions.class.php 2692 2006-11-15 21:03:55Z fabien $
 */
class mainActions extends sfActions
{
  /**
   * Executes index action
   *
   */
  public function executeIndex()
  {
  }

  public function executeManual()
  {
    $this->redirect('http://doctrine.pengus.net/doctrine/manual/new');
  }
  
  public function executeAbout()
  {
    
  }
  
  public function executeDownload()
  {
    
  }
  
  public function executeTrac()
  {
    $this->redirect('http://phpdoctrine.net/trac');
  }
}
