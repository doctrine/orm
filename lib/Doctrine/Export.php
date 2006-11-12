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
/**
 * Doctrine_Export
 *
 * @package     Doctrine
 * @author      Konsta Vesterinen <kvesteri@cc.hut.fi>
 * @license     http://www.opensource.org/licenses/lgpl-license.php LGPL
 * @category    Object Relational Mapping
 * @link        www.phpdoctrine.com
 * @since       1.0
 * @version     $Revision$
 */
class Doctrine_Export {
    /**
     * @var Doctrine_Connection $conn       Doctrine_Connection object
     */
    private $conn;
    /**
     * @var mixed $dbh                      the database handler (either PDO or Doctrine_Db object)
     */
    private $dbh;

    public function __construct($conn) {
        $this->conn = $conn;
        $this->dbh  = $conn->getDBH();
    }
    /**
     * dropTable
     *
     * @param string    $table              name of table that should be dropped from the database
     * @throws PDOException
     * @return void
     */
    public function dropTable($table) {
        $this->dbh->query('DROP TABLE '.$table);
    }
    /**
     * [[ borrowed from PEAR MDB2 ]]
     *
     * Get the stucture of a field into an array
     *
     *
     * @param string    $table         name of the table on which the index is to be created
     * @param string    $name          name of the index to be created
     * @param array     $definition    associative array that defines properties of the index to be created.
     *                                 Currently, only one property named FIELDS is supported. This property
     *                                 is also an associative with the names of the index fields as array
     *                                 indexes. Each entry of this array is set to another type of associative
     *                                 array that specifies properties of the index that are specific to
     *                                 each field.
     *
     *                                 Currently, only the sorting property is supported. It should be used
     *                                 to define the sorting direction of the index. It may be set to either
     *                                 ascending or descending.
     *
     *                                 Not all DBMS support index sorting direction configuration. The DBMS
     *                                 drivers of those that do not support it ignore this property. Use the
     *                                 function supports() to determine whether the DBMS driver can manage indexes.
     *
     *                                 Example
     *                                    array(
     *                                        'fields' => array(
     *                                            'user_name' => array(
     *                                                'sorting' => 'ascending'
     *                                            ),
     *                                            'last_login' => array()
     *                                        )
     *                                    )
     * @throws PDOException
     * @return void
     */
    function createIndex($table, $name, array $definition) {
        $table  = $this->conn->quoteIdentifier($table);
        $name   = $this->conn->quoteIdentifier($name);

        $query = "CREATE INDEX $name ON $table";
        $fields = array();
        foreach (array_keys($definition['fields']) as $field) {
            $fields[] = $this->conn->quoteIdentifier($field);
        }
        $query .= ' ('. implode(', ', $fields) . ')';
        return $this->dbh->query($query);
    }
    /**
     * export
     */
    public function export() {
        $parent = new ReflectionClass('Doctrine_Record');
        $conn   = Doctrine_Manager::getInstance()->getCurrentConnection();
        $old    = $conn->getAttribute(Doctrine::ATTR_CREATE_TABLES);

        $conn->setAttribute(Doctrine::ATTR_CREATE_TABLES, true);
        
        foreach(get_declared_classes() as $name) {
            $class = new ReflectionClass($name);

            if($class->isSubclassOf($parent) && ! $class->isAbstract())
                $obj = new $class();
        }
        $conn->setAttribute(Doctrine::ATTR_CREATE_TABLES, $old);
    }
}
