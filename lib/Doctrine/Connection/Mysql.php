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
Doctrine::autoload('Doctrine_Connection_Common');
/**
 * Doctrine_Connection_Mysql
 *
 * @package     Doctrine ORM
 * @url         www.phpdoctrine.com
 * @license     LGPL
 */
class Doctrine_Connection_Mysql extends Doctrine_Connection_Common {

    /**
     * the constructor
     * @param PDO $pdo  -- database handle
     */
    public function __construct(Doctrine_Manager $manager,PDO $pdo) {
        $pdo->setAttribute(PDO::ATTR_EMULATE_PREPARES, true);
        $this->setAttribute(Doctrine::ATTR_QUERY_LIMIT, Doctrine::LIMIT_ROWS);
        
        $this->supported = array(
                          'sequences'            => 'emulated',
                          'indexes'              => true,
                          'affected_rows'        => true,
                          'transactions'         => true,
                          'savepoints'           => false,
                          'summary_functions'    => true,
                          'order_by_text'        => true,
                          'current_id'           => 'emulated',
                          'limit_queries'        => true,
                          'LOBs'                 => true,
                          'replace'              => true,
                          'sub_selects'          => true,
                          'auto_increment'       => true,
                          'primary_key'          => true,
                          'result_introspection' => true,
                          'prepared_statements'  => 'emulated',
                          'identifier_quoting'   => true,
                          'pattern_escaping'     => true
                          );

        parent::__construct($manager,$pdo);
    }    
    /**
     * returns the regular expression operator 
     * (implemented by the connection drivers)
     *
     * @return string
     */
    public function getRegexpOperator() {
        return 'RLIKE';
    }
    /**
     * getTransactionIsolation
     * 
     * @return string               returns the current session transaction isolation level
     */
    public function getTransactionIsolation() {
        $ret = $this->dbh->query('SELECT @@tx_isolation')->fetch(PDO::FETCH_NUM);
        return $ret[0];
    }
    /**
     * Set the transacton isolation level.
     *
     * @param   string  standard isolation level
     *                  READ UNCOMMITTED (allows dirty reads)
     *                  READ COMMITTED (prevents dirty reads)
     *                  REPEATABLE READ (prevents nonrepeatable reads)
     *                  SERIALIZABLE (prevents phantom reads)
     * @throws Doctrine_Connection_Mysql_Exception      if using unknown isolation level
     * @throws PDOException                             if something fails at the PDO level
     * @return void
     */
    public function setTransactionIsolation($isolation) {
        switch ($isolation) {
            case 'READ UNCOMMITTED':
            case 'READ COMMITTED':
            case 'REPEATABLE READ':
            case 'SERIALIZABLE':
            
            break;
            default:
                throw new Doctrine_Connection_Mysql_Exception('Isolation level ' . $isolation . ' is not supported.');
        }

        $query = "SET SESSION TRANSACTION ISOLATION LEVEL $isolation";
        return $this->dbh->query($query);
    }
    /**
     * Returns string to concatenate two or more string parameters
     *
     * @param string $value1
     * @param string $value2
     * @param string $values...
     * @return string               a concatenation of two or more strings
     */
    public function concat($value1, $value2) {
        $args = func_get_args();
        return "CONCAT(".implode(', ', $args).")";
    }
}

