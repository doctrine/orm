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
 * Doctrine_Connection_Mssql
 *
 * @package     Doctrine ORM
 * @url         www.phpdoctrine.com
 * @license     http://www.opensource.org/licenses/lgpl-license.php LGPL
 * @author      Konsta Vesterinen
 * @author      Lukas Smith <smith@pooteeweet.org> (PEAR MDB2 library)
 * @version     $Id$
 */
class Doctrine_Connection_Mssql extends Doctrine_Connection {
    /**
     * @var string $driverName                  the name of this connection driver
     */
    protected $driverName = 'Mssql';
    /**
     * the constructor
     *
     * @param Doctrine_Manager $manager
     * @param PDO $pdo                          database handle
     */
    public function __construct(Doctrine_Manager $manager, PDO $pdo) {
        // initialize all driver options
        $this->supported = array(
                          'sequences'             => 'emulated',
                          'indexes'               => true,
                          'affected_rows'         => true,
                          'transactions'          => true,
                          'summary_functions'     => true,
                          'order_by_text'         => true,
                          'current_id'            => 'emulated',
                          'limit_queries'         => 'emulated',
                          'LOBs'                  => true,
                          'replace'               => 'emulated',
                          'sub_selects'           => true,
                          'auto_increment'        => true,
                          'primary_key'           => true,
                          'result_introspection'  => true,
                          'prepared_statements'   => 'emulated',
                          );

        parent::__construct($manager, $pdo);
    }
    /**
     * quoteIdentifier
     * Quote a string so it can be safely used as a table / column name
     *
     * Quoting style depends on which database driver is being used.
     *
     * @param string $identifier    identifier name to be quoted
     * @param bool   $checkOption   check the 'quote_identifier' option
     *
     * @return string  quoted identifier string
     */
    public function quoteIdentifier($identifier, $checkOption = false) {
        if ($check_option && ! $this->options['quote_identifier']) {
            return $identifier;
        }
        return '[' . str_replace(']', ']]', $identifier) . ']';
    }
    /**
     * returns the next value in the given sequence
     *
     * @param string $sequence      name of the sequence
     * @return integer              the next value in the given sequence
     */
    public function nextId($sequence) {
        $this->query("INSERT INTO $sequence (vapor) VALUES (0)");
        $stmt = $this->query("SELECT @@IDENTITY FROM $sequence");
        $data = $stmt->fetch(PDO::FETCH_NUM);
        return $data[0];
    }
    /**
     * Adds an adapter-specific LIMIT clause to the SELECT statement.
     * [ borrowed from Zend Framework ]
     *
     * @param string $query
     * @param mixed $limit
     * @param mixed $offset
     * @link http://lists.bestpractical.com/pipermail/rt-devel/2005-June/007339.html
     * @return string
     */
    public function modifyLimitQuery($query, $limit, $offset, $isManip = false) {
        if($limit > 0) {

            // we need the starting SELECT clause for later
            $select = 'SELECT ';
            if (preg_match('/^[[:space:]*SELECT[[:space:]]*DISTINCT/i', $query, $matches) == 1)
                $select .= 'DISTINCT ';

            $length = strlen($select);

            // is there an offset?
            if (! $offset) {
                // no offset, it's a simple TOP count
                return $select . ' TOP ' . $count . substr($query, $length);
            }

            // the total of the count **and** the offset, combined.
            // this will be used in the "internal" portion of the
            // hacked-up statement.
            $total = $count + $offset;

            // build the "real" order for the external portion.
            $order = implode(',', $parts['order']);

            // build a "reverse" order for the internal portion.
            $reverse = $order;
            $reverse = str_ireplace(" ASC",  " \xFF", $reverse);
            $reverse = str_ireplace(" DESC", " ASC",  $reverse);
            $reverse = str_ireplace(" \xFF", " DESC", $reverse);

            // create a main statement that replaces the SELECT
            // with a SELECT TOP
            $main = "\n$select TOP $total" . substr($query, $length) . "\n";

            // build the hacked-up statement.
            // do we really need the "as" aliases here?
            $query = "SELECT * FROM ("
                 . "SELECT TOP $count * FROM ($main) AS select_limit_rev ORDER BY $reverse"
                 . ") AS select_limit ORDER BY $order";

        }

        return $query;
    }
    /**
     * Returns the autoincrement ID if supported or $id or fetches the current
     * ID in a sequence called: $table.(empty($field) ? '' : '_'.$field)
     *
     * @param string $table name of the table into which a new row was inserted
     * @param string $field name of the field into which a new row was inserted
     * @return mixed MDB2 Error Object or id
     * @access public
     */
    function lastInsertID($table = null, $field = null)
    {
        $server_info = $this->getServerVersion();
        if (is_array($server_info)
            && !is_null($server_info['major'])
                && $server_info['major'] >= 8) {
                    $query = "SELECT SCOPE_IDENTITY()";
        } else {
                    $query = "SELECT @@IDENTITY";
        }

        return $this->queryOne($query, 'integer');
    }
}

