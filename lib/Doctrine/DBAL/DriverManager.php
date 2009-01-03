<?php
/*
 *  $Id$
 *
 * THIS SOFTWARE IS PROVIDED BY THE COPYRIGHT HOLDERS AND CONTRIBUTORS
 * "AS IS" AND ANY EXPRESS OR IMPLIED WARRANTIES, INCLUDING, BUT NOT
 * LIMITED TO, THE IMPLIED WARRANTIES OF MERCHANTABILITY AND FITNESS FOR
 * A PARTICULAR PURPOSE ARE DISCLAIMED. IN NO EVENT SHALL THE COPYRIGHT
 * OWNER OR CONTRIBUTORS BE LIABLE FOR ANY DIRECT, INDIRECT, INCIDENTAL,
 * SPECIAL, EXEMPLARY, OR CONSEQUENTIAL DAMAGES (INCLUDING, BUT NOT
 * LIMITED TO, PROCUREMENT OF SUBSTITUTE GOODS OR SERVICES; LOSS OF USE,
 * DATA, OR PROFITS; OR BUSINESS INTERRUPTION) HOWEVER CAUSED AND ON ANY
 * THEORY OF LIABILITY, WHETHER IN CONTRACT, STRICT LIABILITY, OR TORT
 * (INCLUDING NEGLIGENCE OR OTHERWISE) ARISING IN ANY WAY OUT OF THE USE
 * OF THIS SOFTWARE, EVEN IF ADVISED OF THE POSSIBILITY OF SUCH DAMAGE.
 *
 * This software consists of voluntary contributions made by many individuals
 * and is licensed under the LGPL. For more information, see
 * <http://www.phpdoctrine.org>.
 */

#namespace Doctrine\DBAL;

/**
 * Factory for creating Doctrine\DBAL\Connection instances.
 *
 * @author Roman Borschel <roman@code-factory.org>
 * @since 2.0
 */
final class Doctrine_DBAL_DriverManager
{
    /**
     * List of supported drivers and their mappings to the driver class.
     *
     * @var array
     */
     private static $_driverMap = array(
            'pdo_mysql'  => 'Doctrine_DBAL_Driver_PDOMySql_Driver',
            'pdo_sqlite' => 'Doctrine_DBAL_Driver_PDOSqlite_Driver',
            'pdo_pgsql'  => 'Doctrine_DBAL_Driver_PDOPgSql_Driver',
            'pdo_oracle' => 'Doctrine_DBAL_Driver_PDOOracle_Driver',
            'pdo_mssql'  => 'Doctrine_DBAL_Driver_PDOMsSql_Driver',
            'pdo_firebird' => 'Doctrine_DBAL_Driver_PDOFirebird_Driver',
            'pdo_informix' => 'Doctrine_DBAL_Driver_PDOInformix_Driver',
            );

    /** Private constructor. This class cannot be instantiated. */
    private function __construct() {}
            
    /**
     * Creates a connection object based on the specified parameters.
     * This method returns a Doctrine\DBAL\Connection which wraps the underlying
     * driver connection.
     *
     * $params must contain at least one of the following.
     * 
     * Either 'driver' with one of the following values:
     *     pdo_mysql
     *     pdo_sqlite
     *     pdo_pgsql
     *     pdo_oracle
     *     pdo_mssql
     *     pdo_firebird
     *     pdo_informix
     * 
     * OR 'driverClass' that contains the full class name (with namespace) of the
     * driver class to instantiate.
     * 
     * Other (optional) parameters:
     * 
     * <b>user (string)</b>:
     * The username to use when connecting. 
     * 
     * <b>password (string)</b>:
     * The password to use when connecting.
     * 
     * <b>driverOptions (array)</b>:
     * Any additional driver-specific options for the driver. These are just passed
     * through to the driver.
     * 
     * <b>pdo</b>:
     * You can pass an existing PDO instance through this parameter. The PDO
     * instance will be wrapped in a Doctrine\DBAL\Connection.
     * 
     * <b>wrapperClass</b>:
     * You may specify a custom wrapper class through the 'wrapperClass'
     * parameter but this class MUST inherit from Doctrine\DBAL\Connection.
     * 
     * @param array $params The parameters.
     * @param Doctrine\DBAL\Configuration The configuration to use.
     * @param Doctrine\Common\EventManager The event manager to use.
     * @return Doctrine\DBAL\Connection
     */
    public static function getConnection(array $params,
            Doctrine_DBAL_Configuration $config = null,
            Doctrine_Common_EventManager $eventManager = null)
    {
        // create default config and event manager, if not set
        if ( ! $config) {
            $config = new Doctrine_DBAL_Configuration();
        }
        if ( ! $eventManager) {
            $eventManager = new Doctrine_Common_EventManager();
        }
        
        // check for existing pdo object
        if (isset($params['pdo']) && ! $params['pdo'] instanceof PDO) {
            throw Doctrine_DBAL_Exceptions_DBALException::invalidPDOInstance();
        } else if (isset($params['pdo'])) {
            $params['driver'] = $params['pdo']->getAttribute(PDO::ATTR_DRIVER_NAME);
        } else {
            self::_checkParams($params);
        }
        if (isset($params['driverClass'])) {
            $className = $params['driverClass'];
        } else {
            $className = self::$_driverMap[$params['driver']];
        }
        
        $driver = new $className();
        
        $wrapperClass = 'Doctrine_DBAL_Connection';
        if (isset($params['wrapperClass']) && is_subclass_of($params['wrapperClass'], $wrapperClass)) {
            $wrapperClass = $params['wrapperClass'];
        }
        
        return new $wrapperClass($params, $driver, $config, $eventManager);
    }
    
    /**
     * Checks the list of parameters.
     *
     * @param array $params
     */
    private static function _checkParams(array $params)
    {        
        // check existance of mandatory parameters
        
        // driver
        if ( ! isset($params['driver']) && ! isset($params['driverClass'])) {
            throw Doctrine_ConnectionFactory_Exception::driverRequired();
        }
        
        // check validity of parameters
        
        // driver
        if ( isset($params['driver']) && ! isset(self::$_driverMap[$params['driver']])) {
            throw Doctrine_DBAL_Exceptions_DBALException::unknownDriver($params['driver']);
        }
    }
}

?>