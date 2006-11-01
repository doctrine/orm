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
Doctrine::autoload("Doctrine_Connection_Common");
/**
 * Doctrine_Connection_Pgsql
 *
 * @package     Doctrine ORM
 * @url         www.phpdoctrine.com
 * @license     LGPL
 */
class Doctrine_Connection_Pgsql extends Doctrine_Connection_Common {
    /**
     * @var string $driverName                  the name of this connection driver
     */
    protected $driverName = 'Pgsql';
    /**
     * the constructor
     *
     * @param Doctrine_Manager $manager
     * @param PDO $pdo                          database handle
     */
    public function __construct(Doctrine_Manager $manager, PDO $pdo) {
        // initialize all driver options
        $this->supported = array(
                          'sequences'               => true,
                          'indexes'                 => true,
                          'affected_rows'           => true,
                          'summary_functions'       => true,
                          'order_by_text'           => true,
                          'transactions'            => true,
                          'savepoints'              => true,
                          'current_id'              => true,
                          'limit_queries'           => true,
                          'LOBs'                    => true,
                          'replace'                 => 'emulated',
                          'sub_selects'             => true,
                          'auto_increment'          => 'emulated',
                          'primary_key'             => true,
                          'result_introspection'    => true,
                          'prepared_statements'     => true,
                          'identifier_quoting'      => true,
                          'pattern_escaping'        => true,
                          );
                          
        $this->options['multi_query'] = false;

        parent::__construct($manager, $pdo);
    }
    /**
     * Set the charset on the current connection
     *
     * @param string    charset
     *
     * @return void
     */
    public function setCharset($charset) {
        $query = 'SET NAMES '.$this->dbh->quote($charset);
        $this->dbh->query($query);
    }
    /**
     * returns the next value in the given sequence
     * @param string $sequence
     * @return integer
     */
    public function nextId($sequence) {
        $stmt = $this->dbh->query("SELECT NEXTVAL('$sequence')");
        $data = $stmt->fetch(PDO::FETCH_NUM);
        return $data[0];
    }
    /**
     * Returns the current id of a sequence
     *
     * @param string $seq_name name of the sequence
     * @return mixed MDB2 Error Object or id
     * @access public
     */
    function currId($sequence) {
        $stmt = $this->dbh->query('SELECT last_value FROM '.$sequence);
        $data = $stmt->fetch(PDO::FETCH_NUM);
        return $data[0];
    }
    /**
     * Changes a query string for various DBMS specific reasons
     *
     * @param string $query         query to modify
     * @param integer $limit        limit the number of rows
     * @param integer $offset       start reading from given offset
     * @param boolean $isManip      if the query is a DML query

     * @return string               modified query
     */
    public function modifyLimitQuery($query, $limit, $offset, $isManip = false) {
        if ($limit > 0) {
            $query = rtrim($query);
            
            if (substr($query, -1) == ';') {
                $query = substr($query, 0, -1);
            }
            
            if ($isManip) {
                $manip = preg_replace('/^(DELETE FROM|UPDATE).*$/', '\\1', $query);
                $from  = $match[2];
                $where = $match[3];
                $query = $manip . ' ' . $from . ' WHERE ctid=(SELECT ctid FROM ' 
                       . $from . ' ' . $where . ' LIMIT ' . $limit . ')';

            } else {
                $query .= ' LIMIT ' . $limit . ' OFFSET ' . $offset;
            }
        }
        return $query;
    }
    /**
     * Set the transacton isolation level.
     *
     * @param   string  standard isolation level
     *                  READ UNCOMMITTED (allows dirty reads)
     *                  READ COMMITTED (prevents dirty reads)
     *                  REPEATABLE READ (prevents nonrepeatable reads)
     *                  SERIALIZABLE (prevents phantom reads)
     * @return void
     */
    public function setTransactionIsolation($isolation) {
        switch ($isolation) {
            case 'READ UNCOMMITTED':
            case 'READ COMMITTED':
            case 'REPEATABLE READ':
            case 'SERIALIZABLE':
            break;
                throw new Doctrine_Connection_Pgsql_Exception('Isolation level '.$isolation.' is not supported.');
        }

        $query = 'SET SESSION CHARACTERISTICS AS TRANSACTION ISOLATION LEVEL ' . $isolation;
        return $this->dbh->query($query);
    }

}

