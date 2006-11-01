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
 * <http://www.phpdoctrine.com>.
 */
Doctrine::autoload('Doctrine_Connection');
/**
 * Doctrine_Connection_Firebird
 *
 * @package     Doctrine ORM
 * @url         www.phpdoctrine.com
 * @author      Lorenzo Alberton <l.alberton@quipo.it> (PEAR MDB2 library)
 * @license     LGPL
 */
class Doctrine_Connection_Firebird extends Doctrine_Connection {
    /**
     * @var string $driverName                  the name of this connection driver
     */
    protected $driverName = 'Firebird';
    /**
     * the constructor
     *
     * @param Doctrine_Manager $manager
     * @param PDO $pdo                          database handle
     */
    public function __construct(Doctrine_Manager $manager, PDO $pdo) {

        $this->supported = array(
                          'sequences'             => true,
                          'indexes'               => true,
                          'affected_rows'         => true,
                          'summary_functions'     => true,
                          'order_by_text'         => true,
                          'transactions'          => true,
                          'savepoints'            => true,
                          'current_id'            => true,
                          'limit_queries'         => 'emulated',
                          'LOBs'                  => true,
                          'replace'               => 'emulated',
                          'sub_selects'           => true,
                          'auto_increment'        => true,
                          'primary_key'           => true,
                          'result_introspection'  => true,
                          'prepared_statements'   => true,
                          'identifier_quoting'    => false,
                          'pattern_escaping'      => true
                          );
        // initialize all driver options
        $this->options['DBA_username'] = false;
        $this->options['DBA_password'] = false;
        $this->options['database_path'] = '';
        $this->options['database_extension'] = '.gdb';
        $this->options['server_version'] = '';
        parent::__construct($manager, $pdo);
    }
    /**
     * Set the transacton isolation level.
     *
     * @param   string  standard isolation level (SQL-92)
     *                  READ UNCOMMITTED (allows dirty reads)
     *                  READ COMMITTED (prevents dirty reads)
     *                  REPEATABLE READ (prevents nonrepeatable reads)
     *                  SERIALIZABLE (prevents phantom reads)
     *
     * @param   array some transaction options:
     *                  'wait' => 'WAIT' | 'NO WAIT'
     *                  'rw'   => 'READ WRITE' | 'READ ONLY'
     * @return void
     */
    function setTransactionIsolation($isolation, $options = array()) {
        switch ($isolation) {
            case 'READ UNCOMMITTED':
                $ibase_isolation = 'READ COMMITTED RECORD_VERSION';
            break;
            case 'READ COMMITTED':
                $ibase_isolation = 'READ COMMITTED NO RECORD_VERSION';
            break;
            case 'REPEATABLE READ':
                $ibase_isolation = 'SNAPSHOT';
            break;
            case 'SERIALIZABLE':
                $ibase_isolation = 'SNAPSHOT TABLE STABILITY';
            break;
            default:
                throw new Doctrine_Connection_Firebird_Exception('isolation level is not supported: ' . $isolation);
        }

        if( ! empty($options['wait'])) {
            switch ($options['wait']) {
                case 'WAIT':
                case 'NO WAIT':
                    $wait = $options['wait'];
                break;
                default:
                    throw new Doctrine_Connection_Firebird_Exception('wait option is not supported: ' . $options['wait']);
            }
        }

        if( ! empty($options['rw'])) {
            switch ($options['rw']) {
                case 'READ ONLY':
                case 'READ WRITE':
                    $rw = $options['wait'];
                break;
                default:
                    throw new Doctrine_Connection_Firebird_Exception('wait option is not supported: ' . $options['rw']);
            }
        }

        $query = 'SET TRANSACTION ' . $rw . ' ' . $wait .' ISOLATION LEVEL ' . $ibase_isolation;
        $this->dbh->query($query);
    }
    /**
     * Set the charset on the current connection
     *
     * @param string    charset
     * @param resource  connection handle
     *
     * @return void
     */
    public function setCharset($charset) {
        $query = 'SET NAMES '.$this->dbh->quote($charset);
        $this->dbh->query($query);
    }
    /**
     * Adds an driver-specific LIMIT clause to the query
     *
     * @param string $query     query to modify
     * @param integer $limit    limit the number of rows
     * @param integer $offset   start reading from given offset
     * @return string modified  query
     */
    public function modifyLimitQuery($query, $limit, $offset) {
        if($limit > 0) {
            $query = preg_replace('/^([\s(])*SELECT(?!\s*FIRST\s*\d+)/i',
                "SELECT FIRST $limit SKIP $offset", $query);
        }
        return $query;
    }
    /**
     * returns the next value in the given sequence
     * @param string $sequence
     * @return integer
     */
    public function getNextID($sequence) {
        $stmt = $this->query("SELECT UNIQUE FROM ".$sequence);
        $data = $stmt->fetch(PDO::FETCH_NUM);
        return $data[0];
    }
}

