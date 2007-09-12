<?php

/*
 * This file is part of the symfony package.
 * (c) 2004-2006 Fabien Potencier <fabien.potencier@symfony-project.com>
 * (c) 2006-2007 Olivier Verdier <Olivier.Verdier@gmail.com>
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

/**
 * @package    symfony.plugins
 * @subpackage sfDoctrine
 * @version    SVN: $Id: sfDoctrine.class.php 4092 2007-05-23 17:37:26Z chtito $
 */

class sfDoctrine
{
  // uses the default connection if none is given
  static public function connection($connection = null)
  {
    if ($connection === null)
    {
      return Doctrine_Manager::getInstance()->getCurrentConnection();
    }

    return Doctrine_Manager::getInstance()->getConnection($connection);
  }

  // returns either the connection connectionName or uses the doctrine manager
  // to find out the connection bound to the class (or the current one)
  public static function connectionForClass($className, $connectionName = null)
  {
    if (isset($connectionName))
    {
      return Doctrine_Manager::getInstance()->getConnection($connectionName);
    }
    return Doctrine_Manager::getInstance()->getConnectionForComponent($className);
  }

  public static function getTable($className)
  {
    return Doctrine_Manager::getInstance()->getTable($className);
  }

  public static function queryFrom($className)
  {
      sfContext::getInstance()->getLogger()->err('The sfDoctrine::queryFrom()  method is deprecated; use "Doctrine_Query::create()->from($className)" instead.');
     return self::getTable($className)->createQuery();
  }
}
