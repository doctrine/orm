<?php
/*
 * This file is part of the sfDoctrine package.
 * (c) 2006-2007 Olivier Verdier <Olivier.Verdier@gmail.com>
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

/**
 * @package    symfony.plugins
 * @subpackage sfDoctrine
 * @author     Olivier Verdier <Olivier.Verdier@gmail.com>
 * @version    SVN: $Id: sfDoctrineQueryLogger.class.php 4728 2007-07-27 10:42:49Z mahono $
 */

class sfDoctrineQueryLogger extends Doctrine_EventListener
{
  protected $connection = null;
  protected $encoding = 'UTF8';

  public function preExecute(Doctrine_Event $event)
  {
    $this->sfLogQuery('{sfDoctrine Execute} executeQuery : ', $event);
  }

  public function postExecute(Doctrine_Event $event)
  {
    $this->sfAddTime();
  }

  public function postPrepare(Doctrine_Event $event)
  {
    $this->sfAddTime();
  }

  public function preStmtExecute(Doctrine_Event $event)
  {
    $this->sfLogQuery('{sfDoctrine Statement} executeQuery : ', $event);
  }

  public function postStmtExecute(Doctrine_Event $event)
  {
    $this->sfAddTime();
  }

  public function preQuery(Doctrine_Event $event)
  {
    $this->sfLogQuery('{sfDoctrine Query} executeQuery : ', $event);
  }

  public function postQuery(Doctrine_Event $event)
  {
    $this->sfAddTime();
  }

  protected function sfLogQuery($message, $event)
  {
    $message .= $event->getQuery();

    if ($params = $event->getParams())
    {
      $message .= ' - ('.implode(', ', $params) . ' )';
    }

    sfContext::getInstance()->getLogger()->log($message);
    $sqlTimer = sfTimerManager::getTimer('Database (Doctrine)');
  }

  protected function sfAddTime()
  {
    sfTimerManager::getTimer('Database (Doctrine)')->addTime();
  }
}
