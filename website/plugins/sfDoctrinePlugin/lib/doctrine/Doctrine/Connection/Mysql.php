<?php
/*
 *  $Id: Mysql.php 1773 2007-06-19 23:33:04Z zYne $
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
 * @package     Doctrine
 * @license     http://www.opensource.org/licenses/lgpl-license.php LGPL
 * @author      Konsta Vesterinen <kvesteri@cc.hut.fi>
 * @author      Lukas Smith <smith@pooteeweet.org> (PEAR MDB2 library)
 * @version     $Revision: 1773 $
 * @category    Object Relational Mapping
 * @link        www.phpdoctrine.com
 * @since       1.0
 */
class Doctrine_Connection_Mysql extends Doctrine_Connection_Common
{
    /**
     * @var string $driverName                  the name of this connection driver
     */
    protected $driverName = 'Mysql';
    /**
     * the constructor
     *
     * @param Doctrine_Manager $manager
     * @param PDO|Doctrine_Adapter $adapter     database handler
     */
    public function __construct(Doctrine_Manager $manager, $adapter)
    {
        $this->setAttribute(PDO::ATTR_EMULATE_PREPARES, true);
        $this->setAttribute(Doctrine::ATTR_DEFAULT_TABLE_TYPE, 'INNODB');

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

        $this->properties['string_quoting'] = array('start' => "'",
                                                    'end' => "'",
                                                    'escape' => '\\',
                                                    'escape_pattern' => '\\');

        $this->properties['identifier_quoting'] = array('start' => '`',
                                                        'end' => '`',
                                                        'escape' => '`');

        $this->properties['sql_comments'] = array(
                                            array('start' => '-- ', 'end' => "\n", 'escape' => false),
                                            array('start' => '#', 'end' => "\n", 'escape' => false),
                                            array('start' => '/*', 'end' => '*/', 'escape' => false),
                                            );

        $this->properties['varchar_max_length'] = 255;

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
     * Execute a SQL REPLACE query. A REPLACE query is identical to a INSERT
     * query, except that if there is already a row in the table with the same
     * key field values, the REPLACE query just updates its values instead of
     * inserting a new row.
     *
     * The REPLACE type of query does not make part of the SQL standards. Since
     * practically only MySQL implements it natively, this type of query is
     * emulated through this method for other DBMS using standard types of
     * queries inside a transaction to assure the atomicity of the operation.
     *
     * @access public
     *
     * @param string $table name of the table on which the REPLACE query will
     *  be executed.
     * @param array $fields associative array that describes the fields and the
     *  values that will be inserted or updated in the specified table. The
     *  indexes of the array are the names of all the fields of the table. The
     *  values of the array are also associative arrays that describe the
     *  values and other properties of the table fields.
     *
     *  Here follows a list of field properties that need to be specified:
     *
     *    value:
     *          Value to be assigned to the specified field. This value may be
     *          of specified in database independent type format as this
     *          function can perform the necessary datatype conversions.
     *
     *    Default:
     *          this property is required unless the Null property
     *          is set to 1.
     *
     *    type
     *          Name of the type of the field. Currently, all types Metabase
     *          are supported except for clob and blob.
     *
     *    Default: no type conversion
     *
     *    null
     *          Boolean property that indicates that the value for this field
     *          should be set to null.
     *
     *          The default value for fields missing in INSERT queries may be
     *          specified the definition of a table. Often, the default value
     *          is already null, but since the REPLACE may be emulated using
     *          an UPDATE query, make sure that all fields of the table are
     *          listed in this function argument array.
     *
     *    Default: 0
     *
     *    key
     *          Boolean property that indicates that this field should be
     *          handled as a primary key or at least as part of the compound
     *          unique index of the table that will determine the row that will
     *          updated if it exists or inserted a new row otherwise.
     *
     *          This function will fail if no key field is specified or if the
     *          value of a key field is set to null because fields that are
     *          part of unique index they may not be null.
     *
     *    Default: 0
     *
     * @return integer      the number of affected rows
     */
    public function replace($table, array $fields, array $keys)
    {
        $count = count($fields);
        $query = $values = '';
        $keys = $colnum = 0;

        for (reset($fields); $colnum < $count; next($fields), $colnum++) {
            $name = key($fields);

            if ($colnum > 0) {
                $query .= ',';
                $values.= ',';
            }

            $query .= $name;

            if (isset($fields[$name]['null']) && $fields[$name]['null']) {
                $value = 'NULL';
            } else {
                $type = isset($fields[$name]['type']) ? $fields[$name]['type'] : null;
                $value = $this->quote($fields[$name]['value'], $type);
            }

            $values .= $value;

            if (isset($fields[$name]['key']) && $fields[$name]['key']) {
                if ($value === 'NULL') {
                    throw new Doctrine_Connection_Mysql_Exception('key value '.$name.' may not be NULL');
                }
                $keys++;
            }
        }

        if ($keys == 0) {
            throw new Doctrine_Connection_Mysql_Exception('not specified which fields are keys');
        }
        $query = 'REPLACE INTO ' . $table . ' (' . $query . ') VALUES (' . $values . ')';

        return $this->exec($query);
    }
}
