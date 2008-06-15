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

#namespace Doctrine::DBAL;

/**
 * Factory for creating dbms-specific Connection instances.
 *
 * @author Roman Borschel <roman@code-factory.org>
 * @since 2.0
 */
class Doctrine_ConnectionFactory
{
    /**
     * List of supported drivers and their mappings to the driver class.
     *
     * @var array
     */
     private $_drivers = array(
            'mysql'    => 'Doctrine_Connection_Mysql',
            'sqlite'   => 'Doctrine_Connection_Sqlite',
            'pgsql'    => 'Doctrine_Connection_Pgsql',
            'oci'      => 'Doctrine_Connection_Oracle',
            'oci8'     => 'Doctrine_Connection_Oracle',
            'oracle'   => 'Doctrine_Connection_Oracle',
            'mssql'    => 'Doctrine_Connection_Mssql',
            'dblib'    => 'Doctrine_Connection_Mssql',
            'firebird' => 'Doctrine_Connection_Firebird',
            'informix' => 'Doctrine_Connection_Informix',
            'mock'     => 'Doctrine_Connection_Mock');
    
    public function __construct()
    {
        
    }
    
    public function createConnection(array $params)
    {
        // check for existing pdo object
        if (isset($params['pdo']) && ! $params['pdo'] instanceof PDO) {
            throw Doctrine_ConnectionFactory_Exception::invalidPDOInstance();
        } else if (isset($params['pdo'])) {
            $params['driver'] = $params['pdo']->getAttribute(PDO::ATTR_DRIVER_NAME);
        } else {
            $this->_checkParams($params);
        }
        $className = $this->_drivers[$params['driver']];
        
        return new $className($params);
    }
    
    /**
     * Checks the list of parameters.
     *
     * @param array $params
     */
    private function _checkParams(array $params)
    {        
        // check existance of mandatory parameters
        
        // driver
        if ( ! isset($params['driver'])) {
            throw Doctrine_ConnectionFactory_Exception::driverRequired();
        }
        // user
        if ( ! isset($params['user'])) {
            throw Doctrine_ConnectionFactory_Exception::userRequired();
        }
        // password
        if ( ! isset($params['password'])) {
            throw Doctrine_ConnectionFactory_Exception::passwordRequired();
        }
        
        // check validity of parameters
        
        // driver
        if ( ! isset($this->_drivers[$params['driver']])) {
            throw Doctrine_ConnectionFactory_Exception::unknownDriver($driverName);
        }
    }
}

?>