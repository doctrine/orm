<?php

namespace Doctrine\DBAL\Exceptions;

use Doctrine\Common\DoctrineException;

/**
 * 
 *
 * @package     Doctrine
 * @subpackage  Hydrate
 * @license     http://www.opensource.org/licenses/lgpl-license.php LGPL
 * @link        www.phpdoctrine.org
 * @since       1.0
 * @version     $Revision: 1080 $
 * @author      Konsta Vesterinen <kvesteri@cc.hut.fi>
 */
class DBALException extends DoctrineException
{
    public static function invalidPDOInstance()
    {
        return new self("Invalid PDO instance provided on connection creation.");
    }

    public static function driverRequired()
    {
        return new self("Please provide a driver or a driverClass to be able to start a Connection.");
    }
    
    public static function unknownDriver($driver)
    {
        return new self("Unknown Connection driver '$driver'.");
    }
}