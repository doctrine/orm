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
 * @package     Doctrine
 * @license     http://www.opensource.org/licenses/lgpl-license.php LGPL
 * @author      Konsta Vesterinen <kvesteri@cc.hut.fi>
 * @author      Lukas Smith <smith@pooteeweet.org> (PEAR MDB2 library)
 * @version     $Revision$
 * @category    Object Relational Mapping
 * @link        www.phpdoctrine.com
 * @since       1.0
 */
class Doctrine_Connection_Mssql extends Doctrine_Connection
{
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
    public function __construct(Doctrine_Manager $manager, $adapter)
    {
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

        parent::__construct($manager, $adapter);
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
    public function quoteIdentifier($identifier, $checkOption = false)
    {
        if ($checkOption && ! $this->options['quote_identifier']) {
            return $identifier;
        }
        return '[' . str_replace(']', ']]', $identifier) . ']';
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
    public function modifyLimitQuery($query, $limit, $offset, $isManip = false)
    {
        if ($limit > 0) {
            // we need the starting SELECT clause for later
            $select = 'SELECT ';
            if (preg_match('/^[[:space:]*SELECT[[:space:]]*DISTINCT/i', $query, $matches) == 1) {
                $select .= 'DISTINCT ';
            }
            $length = strlen($select);

            // is there an offset?
            if (! $offset) {
                // no offset, it's a simple TOP count
                return $select . ' TOP ' . $limit . substr($query, $length);
            }

            // the total of the count **and** the offset, combined.
            // this will be used in the "internal" portion of the
            // hacked-up statement.
            $total = $limit + $offset;

            // build the "real" order for the external portion.
            $order = implode(',', $parts['order']);

            // build a "reverse" order for the internal portion.
            $reverse = $order;
            $reverse = str_ireplace(" ASC",  " \xFF", $reverse);
            $reverse = str_ireplace(" DESC", " ASC",  $reverse);
            $reverse = str_ireplace(" \xFF", " DESC", $reverse);

            // create a main statement that replaces the SELECT
            // with a SELECT TOP
            $main = $select . ' TOP ' . $total . substr($query, $length);

            // build the hacked-up statement.
            // do we really need the "as" aliases here?
            $query = 'SELECT * FROM ('
                   . 'SELECT TOP ' . $count . ' * FROM (' . $main . ') AS select_limit_rev ORDER BY '. $reverse
                   . ') AS select_limit ORDER BY ' . $order;

        }

        return $query;
    }
    /**
     * return version information about the server
     *
     * @param bool   $native  determines if the raw version string should be returned
     * @return mixed array/string with version information or MDB2 error object
     * @access public
     */
    function getServerVersion($native = false)
    {
        if ($this->connected_server_info) {
            $serverInfo = $this->connected_server_info;
        } else {
            $query      = 'SELECT @@VERSION';
            $serverInfo = $this->fetchOne($query);
        }
        // cache server_info
        $this->connected_server_info = $serverInfo;
        if ( ! $native) {
            if (preg_match('/([0-9]+)\.([0-9]+)\.([0-9]+)/', $serverInfo, $tmp)) {
                $serverInfo = array(
                    'major' => $tmp[1],
                    'minor' => $tmp[2],
                    'patch' => $tmp[3],
                    'extra' => null,
                    'native' => $serverInfo,
                );
            } else {
                $serverInfo = array(
                    'major' => null,
                    'minor' => null,
                    'patch' => null,
                    'extra' => null,
                    'native' => $serverInfo,
                );
            }
        }
        return $serverInfo;
    }
    /**
     * Checks if there's a sequence that exists.
     *
     * @param  string $seq_name     The sequence name to verify.
     * @return boolean              The value if the table exists or not
     */
    public function checkSequence($seqName)
    {
        $query       = 'SELECT * FROM ' . $seqName;
        try {
            $this->exec($query);
        } catch(Doctrine_Connection_Exception $e) {
            if ($e->getPortableCode() == Doctrine::ERR_NOSUCHTABLE) {
                return false;
            }

            throw $e;
        }
        return true;
    }
}
