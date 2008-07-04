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

#namespace Doctrine::DBAL::Connections;

/**
 * Doctrine_Connection_Sqlite
 *
 * @package     Doctrine
 * @subpackage  Connection
 * @license     http://www.opensource.org/licenses/lgpl-license.php LGPL
 * @author      Konsta Vesterinen <kvesteri@cc.hut.fi>
 * @author      Lukas Smith <smith@pooteeweet.org> (PEAR MDB2 library)
 * @version     $Revision$
 * @link        www.phpdoctrine.org
 * @since       1.0
 */
class Doctrine_Connection_Sqlite extends Doctrine_Connection_Common
{
    /**
     * @var string $driverName                  the name of this connection driver
     */
    protected $_driverName = 'Sqlite';

    /**
     * the constructor
     *
     * @param Doctrine_Manager $manager
     * @param PDO $pdo                          database handle
     */
    public function __construct(array $params)
    {
        $this->supported = array(
                          'sequences'            => 'emulated',
                          'indexes'              => true,
                          'affected_rows'        => true,
                          'summary_functions'    => true,
                          'order_by_text'        => true,
                          'current_id'           => 'emulated',
                          'limit_queries'        => true,
                          'LOBs'                 => true,
                          'replace'              => true,
                          'transactions'         => true,
                          'savepoints'           => false,
                          'sub_selects'          => true,
                          'auto_increment'       => true,
                          'primary_key'          => true,
                          'result_introspection' => false, // not implemented
                          'prepared_statements'  => 'emulated',
                          'identifier_quoting'   => true,
                          'pattern_escaping'     => false,
                          );
                          
        parent::__construct($params);

        if ($this->_isConnected) {
            $this->_pdo->sqliteCreateFunction('mod', array('Doctrine_Expression_Sqlite', 'modImpl'), 2);
            $this->_pdo->sqliteCreateFunction('md5', 'md5', 1);
            $this->_pdo->sqliteCreateFunction('now', 'time', 0);
        }
    }

    /**
     * initializes database functions missing in sqlite
     *
     * @see Doctrine_Expression
     * @return void
     */
    public function connect() 
    {
        if ($this->_isConnected) {
            return false;
        }

        parent::connect();

        $this->_pdo->sqliteCreateFunction('mod', array('Doctrine_Expression_Sqlite', 'modImpl'), 2);
        $this->_pdo->sqliteCreateFunction('md5', 'md5', 1);
        $this->_pdo->sqliteCreateFunction('now', 'time', 0);
    }

    /**
     * createDatabase
     *
     * @return void
     */
    public function createDatabase()
    {
      try {
          if ( ! $dsn = $this->getOption('dsn')) {
              throw new Doctrine_Connection_Exception('You must create your Doctrine_Connection by using a valid Doctrine style dsn in order to use the create/drop database functionality');
          }

          $info = $this->getManager()->parseDsn($dsn);

          $this->export->createDatabase($info['database']);

          return 'Successfully created database for connection "' . $this->getName() . '" at path "' . $info['database'] . '"';
      } catch (Exception $e) {
          return $e;
      }
    }

    /**
     * dropDatabase
     *
     * @return void
     */
    public function dropDatabase()
    {
        try {
            if ( ! $dsn = $this->getOption('dsn')) {
                throw new Doctrine_Connection_Exception('You must create your Doctrine_Connection by using a valid Doctrine style dsn in order to use the create/drop database functionality');
            }

            $info = $this->getManager()->parseDsn($dsn);

            $this->export->dropDatabase($info['database']);

            return 'Successfully dropped database for connection "' . $this->getName() . '" at path "' . $info['database'] . '"';
        } catch (Exception $e) {
            return $e;
        }
    }
    
    /**
     * Constructs the Sqlite PDO DSN.
     * 
     * Overrides Connection#_constructPdoDsn().
     *
     * @return string  The DSN.
     */
    protected function _constructPdoDsn()
    {
        $dsn = 'sqlite:';
        if (isset($this->_params['path'])) {
            $dsn .= $this->_params['path'];
        } else if (isset($this->_params['memory'])) {
            $dsn .= ':memory:';
        }
        
        return $dsn;
    }
}