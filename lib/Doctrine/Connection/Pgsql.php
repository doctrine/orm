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
 * @package     Doctrine
 * @license     http://www.opensource.org/licenses/lgpl-license.php LGPL
 * @author      Konsta Vesterinen <kvesteri@cc.hut.fi>
 * @author      Lukas Smith <smith@pooteeweet.org> (PEAR MDB2 library)
 * @version     $Revision$
 * @category    Object Relational Mapping
 * @link        www.phpdoctrine.com
 * @since       1.0
 */
class Doctrine_Connection_Pgsql extends Doctrine_Connection_Common
{
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
    public function __construct(Doctrine_Manager $manager, $adapter)
    {
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

        $this->properties['string_quoting'] = array('start' => "'",
                                                    'end' => "'",
                                                    'escape' => "'",
                                                    'escape_pattern' => '\\');

        $this->properties['identifier_quoting'] = array('start' => '"',
                                                        'end' => '"',
                                                        'escape' => '"');
        parent::__construct($manager, $adapter);
    }
    /**
     * Set the charset on the current connection
     *
     * @param string    charset
     *
     * @return void
     */
    public function setCharset($charset)
    {
        $query = 'SET NAMES '.$this->dbh->quote($charset);
        $this->exec($query);
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
    public function modifyLimitQuery($query, $limit = false, $offset = false, $isManip = false)
    {
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
                if ($limit !== false) {
                  $query .= ' LIMIT ' . $limit;
                }
                if ($offset !== false) {
                  $query .= ' OFFSET ' . $offset;
                }
            }
        }
        return $query;
    }
    /**
     * return version information about the server
     *
     * @param string $native    determines if the raw version string should be returned
     * @return array|string     an array or string with version information
     */
    public function getServerVersion($native = false)
    {
        $query = 'SHOW SERVER_VERSION';

        $serverInfo = $this->fetchOne($query);

        if ( ! $native) {
            $tmp = explode('.', $serverInfo, 3);

            if (empty($tmp[2]) && isset($tmp[1])
                && preg_match('/(\d+)(.*)/', $tmp[1], $tmp2)
            ) {
                $serverInfo = array(
                    'major' => $tmp[0],
                    'minor' => $tmp2[1],
                    'patch' => null,
                    'extra' => $tmp2[2],
                    'native' => $serverInfo,
                );
            } else {
                $serverInfo = array(
                    'major' => isset($tmp[0]) ? $tmp[0] : null,
                    'minor' => isset($tmp[1]) ? $tmp[1] : null,
                    'patch' => isset($tmp[2]) ? $tmp[2] : null,
                    'extra' => null,
                    'native' => $serverInfo,
                );
            }
        }
        return $serverInfo;
    }
}
