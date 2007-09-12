<?php

/*
 * This file is part of the symfony package.
 * (c) 2004-2006 Fabien Potencier <fabien.potencier@symfony-project.com>
 * (c) 2004-2006 Sean Kerr.
 * (c) 2006-2007 Olivier Verdier <olivier.verdier@gmail.com>
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

/**
 * sfDoctrineDatabase provides connectivity for the Doctrine.
 *
 * @package    symfony.plugins
 * @subpackage sfDoctrine
 * @author     Maarten den Braber <mdb@twister.cx>
 * @author     Olivier Verdier <Olivier.Verdier@gmail.com>
 * @author     Dan Porter
 * @version    SVN: $Id: sfDoctrineDatabase.class.php 4394 2007-06-25 17:59:38Z subzero2000 $
 */
class sfDoctrineDatabase extends sfDatabase
{
  protected
    $doctrineConnection = null;

  public function initialize($parameters = array(), $name = null)
  {
    parent::initialize($parameters);

    // if a default connection is defined we only open that one
    if ($defaultDatabase = sfConfig::get('sf_default_database'))
    {
      if ($name != $defaultDatabase)
      {
        return;
      }
    }

    // load doctrine config
    require(sfConfigCache::getInstance()->checkConfig('config/doctrine.yml'));
    
    $db_attributes = $default_attributes;
    
    if (isset($attributes[$name]))
    {
      $db_attributes = array_merge($default_attributes, $attributes[$name]);
    }
    
    $this->setParameter('attributes', $db_attributes);
    $this->setParameter('name', $name);

    // take care of the component binding
    // suppress errors from include_once
    // because config/schemas.yml is optional.
    @include_once(sfConfigCache::getInstance()->checkConfig('config/schemas.yml', true));

    // opening the doctrine connection
    // determine how to get our parameters
    $method = $this->getParameter('method', 'dsn');

    // get parameters
    switch ($method)
    {
      case 'dsn':
        $dsn = $this->getParameter('dsn');

        if ($dsn == null)
        {
          // missing required dsn parameter
          $error = 'Database configuration specifies method "dsn", but is missing dsn parameter';

          throw new sfDatabaseException($error);
        }
        break;
    }

    try
    {
      // Make sure we pass non-PEAR style DSNs as an array
      if ( ! strpos($dsn, '://'))
      {
        $dsn = array($dsn, $this->getParameter('username'), $this->getParameter('password'));
      }

      $this->doctrineConnection = Doctrine_Manager::connection($dsn, $name);

      // figure out the encoding
      $encoding = $this->getParameter('encoding', 'UTF8');

      // set up the connection parameters from the doctrine.yml config file
      foreach($this->getParameter('attributes') as $k => $v)
      {
        $this->doctrineConnection->setAttribute(constant('Doctrine::'.$k), $v);
      }

      // we add the listener that sets up encoding and date formats
      $eventListener = new sfDoctrineConnectionListener($this->doctrineConnection, $encoding);
      $this->doctrineConnection->addListener($eventListener);

      // add the query logger
      if (sfConfig::get('sf_debug') && sfConfig::get('sf_logging_enabled'))
      {
        $this->doctrineConnection->addListener(new sfDoctrineQueryLogger());
      }
    }
    catch (PDOException $e)
    {
      throw new sfDatabaseException($e->getMessage());
    }
  }

  /**
   * Connect to the database.
   * Stores the PDO connection in $connection
   *
   */
  public function connect ()
  {
    $dbh = $this->doctrineConnection->getDbh();
    $dbh->connect();
    $this->connection = $dbh->getDbh();
  }

  /**
   * Execute the shutdown procedure.
   *
   * @return void
   *
   * @throws <b>sfDatabaseException</b> If an error occurs while shutting down this database.
   */
  public function shutdown ()
  {
    if ($this->connection !== null)
    {
      @$this->connection = null;
    }
  }

}
