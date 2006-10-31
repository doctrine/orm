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
 * Doctrine_Connection_Oracle
 *
 * @package     Doctrine ORM
 * @url         www.phpdoctrine.com
 * @license     LGPL
 */
class Doctrine_Connection_Oracle extends Doctrine_Connection {
    /**
     * @var string $driverName                  the name of this connection driver
     */
    protected $driverName = 'Oracle';
    
    
    public function __construct(Doctrine_Manager $manager, PDO $pdo) {
        $this->supported = array(
                          'sequences'            => true,
                          'indexes'              => true,
                          'summary_functions'    => true,
                          'order_by_text'        => true,
                          'current_id'           => true,
                          'affected_rows'        => true,
                          'transactions'         => true,
                          'savepoints'           => true,
                          'limit_queries'        => true,
                          'LOBs'                 => true,
                          'replace'              => 'emulated',
                          'sub_selects'          => true,
                          'auto_increment'       => false, // implementation is broken
                          'primary_key'          => true,
                          'result_introspection' => true,
                          'prepared_statements'  => true,
                          'identifier_quoting'   => true,
                          'pattern_escaping'     => true,
                          );

        $this->options['DBA_username'] = false;
        $this->options['DBA_password'] = false;
        $this->options['database_name_prefix'] = false;
        $this->options['emulate_database'] = true;
        $this->options['default_tablespace'] = false;
        $this->options['default_text_field_length'] = 2000;
        $this->options['result_prefetching'] = false;
        
        parent::__construct($manager, $pdo);
    }
    /**
     * Adds an driver-specific LIMIT clause to the query
     *
     * @param string $query         query to modify
     * @param integer $limit        limit the number of rows
     * @param integer $offset       start reading from given offset
     * @return string               the modified query
     */
    public function modifyLimitQuery($query, $limit, $offset) {
        /**
        $e      = explode("select ",strtolower($query));
        $e2     = explode(" from ",$e[1]);
        $fields = $e2[0];
        */
        if (preg_match('/^\s*SELECT/i', $query)) {
            if ( ! preg_match('/\sFROM\s/i', $query)) {
                $query .= " FROM dual";
            }
            if ($limit > 0) {
                // taken from http://svn.ez.no/svn/ezcomponents/packages/Database
                $max = $offset + $limit;
                if ($offset > 0) {
                    $min = $offset + 1;
                    $query = 'SELECT * FROM (SELECT a.*, ROWNUM dctrn_rownum FROM (' . $query
                           . ') a WHERE ROWNUM <= ' . $max . ') WHERE dctrn_rownum >= ' . $min;
                } else {
                    $query = 'SELECT a.* FROM (' . $query .') a WHERE ROWNUM <= ' . $max;
                }
            }
        }
        return $query;
    }
    /**
     * Set the transacton isolation level.
     *
     * example:
     *
     * <code>
     * $conn->setTransactionIsolation('READ UNCOMMITTED');
     * </code>
     *
     * @param   string  standard isolation level
     *                  READ UNCOMMITTED (allows dirty reads)
     *                  READ COMMITTED (prevents dirty reads)
     *                  REPEATABLE READ (prevents nonrepeatable reads)
     *                  SERIALIZABLE (prevents phantom reads)
     * @throws PDOException                         if something fails at the PDO level
     * @return void
     */
    public function setTransactionIsolation($isolation) {
        switch ($isolation) {
            case 'READ UNCOMMITTED':
                $isolation = 'READ COMMITTED';
            case 'READ COMMITTED':
            case 'REPEATABLE READ':
                $isolation = 'SERIALIZABLE';
            case 'SERIALIZABLE':
                break;
            default:
                throw new Doctrine_Connection_Oracle_Exception('Isolation level ' . $isolation . ' is not supported.');
        }

        $query = 'ALTER SESSION ISOLATION LEVEL ' . $isolation;
        return $this->dbh->query($query);
    }
    /**
     * returns the next value in the given sequence
     *
     * @param string $sequence      name of the sequence
     * @throws PDOException         if something went wrong at database level
     * @return integer
     */
    public function nextId($sequence) {
        $stmt = $this->query('SELECT ' . $sequence . '.nextval FROM dual');
        $data = $stmt->fetch(PDO::FETCH_NUM);
        return $data[0];
    }
    /**
     * Returns the current id of a sequence
     *
     * @param string $sequence      name of the sequence
     * @throws PDOException         if something went wrong at database level
     * @return mixed id
     */
    public function currId($sequence) {
        $sequence = $this->quoteIdentifier($this->getSequenceName($sequence), true);
        $stmt = $this->query('SELECT ' . $sequence . '.currval FROM dual');
        $data = $stmt->fetch(PDO::FETCH_NUM);
        return $data[0];
    }
}

