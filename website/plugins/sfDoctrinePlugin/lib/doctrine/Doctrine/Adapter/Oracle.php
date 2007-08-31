<?php
/*
 *  $Id: Mock.php 1080 2007-02-10 18:17:08Z romanb $
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
 * <http://www.phpdoctrine.com>.
 */
Doctrine::autoload('Doctrine_Adapter');
/**
 * Doctrine_Adapter_Oracle
 * [BORROWED FROM ZEND FRAMEWORK]
 *
 * @package     Doctrine
 * @subpackage  Doctrine_Adapter
 * @author      Konsta Vesterinen <kvesteri@cc.hut.fi>
 * @license     http://www.opensource.org/licenses/lgpl-license.php LGPL
 * @category    Object Relational Mapping
 * @link        www.phpdoctrine.com
 * @since       1.0
 * @version     $Revision: 1080 $
 */
class Doctrine_Adapter_Oracle extends Doctrine_Adapter
{
    /**
     * User-provided configuration.
     *
     * Basic keys are:
     *
     * username => (string) Connect to the database as this username.
     * password => (string) Password associated with the username.
     * dbname   => Either the name of the local Oracle instance, or the
     *             name of the entry in tnsnames.ora to which you want to connect.
     *
     * @var array
     */
    protected $_config = array(
        'dbname'       => null,
        'username'     => null,
        'password'     => null,
    );

    /**
     * @var integer
     */
    protected $_execute_mode = OCI_COMMIT_ON_SUCCESS;

    /**
     * Constructor.
     *
     * $config is an array of key/value pairs containing configuration
     * options.  These options are common to most adapters:
     *
     * username => (string) Connect to the database as this username.
     * password => (string) Password associated with the username.
     * dbname   => Either the name of the local Oracle instance, or the
     *             name of the entry in tnsnames.ora to which you want to connect.
     *
     * @param array $config An array of configuration keys.
     * @throws Doctrine_Adapter_Exception
     */
    public function __construct(array $config)
    {
        if ( ! isset($config['password']) || ! isset($config['username'])) {
            throw new Doctrine_Adapter_Exception('config array must have at least a username and a password');
        }

        // @todo Let this protect backward-compatibility for one release, then remove
        if ( ! isset($config['database']) || ! isset($config['dbname'])) {
            $config['dbname'] = $config['database'];
            unset($config['database']);
            trigger_error("Deprecated config key 'database', use 'dbname' instead.", E_USER_NOTICE);
        }

        // keep the config
        $this->_config = array_merge($this->_config, (array) $config);

        // create a profiler object
        $enabled = false;
        if (array_key_exists('profiler', $this->_config)) {
            $enabled = (bool) $this->_config['profiler'];
            unset($this->_config['profiler']);
        }

        $this->_profiler = new Doctrine_Profiler($enabled);
    }

    /**
     * Creates a connection resource.
     *
     * @return void
     * @throws Doctrine_Adapter_Oracle_Exception
     */
    protected function _connect()
    {
        if (is_resource($this->_connection)) {
            // connection already exists
            return;
        }

        if (!extension_loaded('oci8')) {
            throw new Doctrine_Adapter_Oracle_Exception('The OCI8 extension is required for this adapter but not loaded');
        }

        if (isset($this->_config['dbname'])) {
            $this->_connection = @oci_connect(
                $this->_config['username'],
                $this->_config['password'],
                $this->_config['dbname']);
        } else {
            $this->_connection = oci_connect(
                $this->_config['username'],
                $this->_config['password']);
        }

        // check the connection
        if (!$this->_connection) {
            throw new Doctrine_Adapter_Oracle_Exception(oci_error());
        }
    }

    /**
     * Force the connection to close.
     *
     * @return void
     */
    public function closeConnection()
    {
        if (is_resource($this->_connection)) {
            oci_close($this->_connection);
        }
        $this->_connection = null;
    }

    /**
     * Returns an SQL statement for preparation.
     *
     * @param string $sql The SQL statement with placeholders.
     * @return Doctrine_Statement_Oracle
     */
    public function prepare($sql)
    {
        $this->_connect();
        $stmt = new Doctrine_Statement_Oracle($this, $sql);
        $stmt->setFetchMode($this->_fetchMode);
        return $stmt;
    }

    /**
     * Quote a raw string.
     *
     * @param string $value     Raw string
     * @return string           Quoted string
     */
    protected function _quote($value)
    {
        $value = str_replace("'", "''", $value);
        return "'" . addcslashes($value, "\000\n\r\\\032") . "'";
    }

    /**
     * Quote a table identifier and alias.
     *
     * @param string|array|Doctrine_Expr $ident The identifier or expression.
     * @param string $alias An alias for the table.
     * @return string The quoted identifier and alias.
     */
    public function quoteTableAs($ident, $alias)
    {
        // Oracle doesn't allow the 'AS' keyword between the table identifier/expression and alias.
        return $this->_quoteIdentifierAs($ident, $alias, ' ');
    }
    /**
     * Leave autocommit mode and begin a transaction.
     *
     * @return void
     */
    protected function _beginTransaction()
    {
        $this->_setExecuteMode(OCI_DEFAULT);
    }
    /**
     * Commit a transaction and return to autocommit mode.
     *
     * @return void
     * @throws Doctrine_Adapter_Oracle_Exception
     */
    protected function _commit()
    {
        if (!oci_commit($this->_connection)) {
            throw new Doctrine_Adapter_Oracle_Exception(oci_error($this->_connection));
        }
        $this->_setExecuteMode(OCI_COMMIT_ON_SUCCESS);
    }
    /**
     * Roll back a transaction and return to autocommit mode.
     *
     * @return void
     * @throws Doctrine_Adapter_Oracle_Exception
     */
    protected function _rollBack()
    {
        if (!oci_rollback($this->_connection)) {
            throw new Doctrine_Adapter_Oracle_Exception(oci_error($this->_connection));
        }
        $this->_setExecuteMode(OCI_COMMIT_ON_SUCCESS);
    }

    /**
     * Set the fetch mode.
     *
     * @todo Support FETCH_CLASS and FETCH_INTO.
     *
     * @param integer $mode A fetch mode.
     * @return void
     * @throws Doctrine_Adapter_Exception
     */
    public function setFetchMode($mode)
    {
        switch ($mode) {
            case Doctrine::FETCH_NUM:   // seq array
            case Doctrine::FETCH_ASSOC: // assoc array
            case Doctrine::FETCH_BOTH:  // seq+assoc array
            case Doctrine::FETCH_OBJ:   // object
                $this->_fetchMode = $mode;
                break;
            default:
                throw new Doctrine_Adapter_Exception('Invalid fetch mode specified');
                break;
        }
    }
    /**
     * @param integer $mode
     * @throws Doctrine_Adapter_Exception
     */
    private function _setExecuteMode($mode)
    {
        switch($mode) {
            case OCI_COMMIT_ON_SUCCESS:
            case OCI_DEFAULT:
            case OCI_DESCRIBE_ONLY:
                $this->_execute_mode = $mode;
                break;
            default:
                throw new Doctrine_Adapter_Exception('wrong execution mode specified');
                break;
        }
    }
    /**
     * @return
     */
    public function _getExecuteMode()
    {
        return $this->_execute_mode;
    }
}
