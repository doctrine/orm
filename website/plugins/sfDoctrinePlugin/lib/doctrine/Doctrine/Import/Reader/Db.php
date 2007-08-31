<?php
/*
 *  $Id: Db.php 1080 2007-02-10 18:17:08Z romanb $
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
Doctrine::autoload('Doctrine_Import_Reader');
/**
 * @package     Doctrine
 * @url         http://www.phpdoctrine.com
 * @license     http://www.opensource.org/licenses/lgpl-license.php LGPL
 * @author      Jukka Hassinen <Jukka.Hassinen@BrainAlliance.com>
 * @version     $Id: Db.php 1080 2007-02-10 18:17:08Z romanb $
 */
/**
 * class Doctrine_Import_Reader_Db
 * Reads a database using the given PDO connection and constructs a database
 * schema
 * @package     Doctrine
 * @category    Object Relational Mapping
 * @link        www.phpdoctrine.com
 * @license     http://www.opensource.org/licenses/lgpl-license.php LGPL
 * @since       1.0
 * @version     $Revision: 1080 $
 * @author      Konsta Vesterinen <kvesteri@cc.hut.fi>
 */
class Doctrine_Import_Reader_Db extends Doctrine_Import_Reader
{

    /** Aggregations: */

    /** Compositions: */

     /*** Attributes: ***/

    /**
     * @access private
     */
    private $pdo;

    /**
     *
     * @param object pdo      * @return
     * @access public
     */
    public function setPdo( $pdo )
    {

    } // end of member function setPdo

    /**
     *
     * @return Doctrine_Schema
     * @access public
     */
    public function read( )
    {
        $dataDict = Doctrine_Manager::getInstance()->getCurrentConnection()->getDataDict();

        $schema = new Doctrine_Schema(); /* @todo FIXME i am incomplete*/
        $db = new Doctrine_Schema_Database();
        $schema->addDatabase($db);

        $dbName = 'XXtest'; // @todo FIXME where should we get

        $this->conn->set("name",$dbName);
        $tableNames = $dataDict->listTables();
        foreach ($tableNames as $tableName){
            $table = new Doctrine_Schema_Table();
            $table->set("name",$tableName);
            $tableColumns = $dataDict->listTableColumns($tableName);
            foreach ($tableColumns as $tableColumn){
                $table->addColumn($tableColumn);
            }
            $this->conn->addTable($table);
            if ($fks = $dataDict->listTableConstraints($tableName)){
                foreach ($fks as $fk){
                    $relation = new Doctrine_Schema_Relation();
                    $relation->setRelationBetween($fk['referencingColumn'],$fk['referencedTable'],$fk['referencedColumn']);
                    $table->setRelation($relation);
                }
            }
        }

        return $schema;
    }

} // end of Doctrine_Import_Reader_Db
