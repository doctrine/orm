<?php
/*
 *  $Id: Export.php 4805 2008-08-25 19:11:58Z subzero2000 $
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
 * <http://www.phpdoctrine.org>.
 */

#namespace Doctrine\ORM\Export;

/**
 * The ClassExporter can generate database schemas/structures from ClassMetadata
 * class descriptors.
 *
 * @package     Doctrine
 * @subpackage  Export
 * @author      Konsta Vesterinen <kvesteri@cc.hut.fi>
 * @author      Lukas Smith <smith@pooteeweet.org> (PEAR MDB2 library)
 * @author      Roman Borschel <roman@code-factory.org>
 * @license     http://www.opensource.org/licenses/lgpl-license.php LGPL
 * @link        www.doctrine-project.org
 * @since       2.0
 * @version     $Revision: 4805 $
 */
class Doctrine_ORM_Export_ClassExporter
{
    /** The SchemaManager */
    private $_sm;
    /** The EntityManager */
    private $_em;

    public function __construct(Doctrine_ORM_EntityManager $em)
    {
        $this->_em = $em;
        $this->_sm = $em->getConnection()->getSchemaManager();
    }

    /**
     * Exports entity classes to a schema.
     *
     * FIXME: This method is a big huge hack. The sql needs to be executed in the correct order. I have some stupid logic to 
     * make sure they are in the right order.
     *
     * @param array $classes
     * @return void
     */
    public function exportClasses(array $classes)
    {
        //TODO: order them
        foreach ($classes as $class) {
            $columns = array();
            $options = array();

            foreach ($class->getFieldMappings() as $fieldName => $mapping) {
                $column = array();
                $column['name'] = $mapping['columnName'];
                $column['type'] = $mapping['type'];
                $column['length'] = $mapping['length'];

                if ($class->isIdentifier($fieldName)) {
                    if ($class->isIdGeneratorIdentity()) {
                        $column['autoincrement'] = true;
                    }
                }

                $columns[$mapping['columnName']] = $column;
            }

            $this->_sm->createTable($class->getTableName(), $columns, $options);
        }
    }

    /**
     * exportClassesSql
     * method for exporting entity classes to a schema
     *
     * @throws Doctrine_Connection_Exception    if some error other than Doctrine::ERR_ALREADY_EXISTS
     *                                          occurred during the create table operation
     * @param array $classes
     * @return void
     */
    public function exportClassesSql(array $classes)
    {
        $models = Doctrine::filterInvalidModels($classes);

        $sql = array();
        $finishedClasses = array();
        
        foreach ($models as $name) {
            if (in_array($name, $finishedClasses)) {
                continue;
            }
            
            $classMetadata = $this->conn->getClassMetadata($name);
            
            // In Class Table Inheritance we have to make sure that ALL tables of parent classes
            // are exported, too as soon as ONE table is exported, because the data of one class is stored
            // across many tables.
            if ($classMetadata->getInheritanceType() == Doctrine::INHERITANCE_TYPE_JOINED) {
                $parents = $classMetadata->getParentClasses();
                foreach ($parents as $parent) {
                    $data = $classMetadata->getConnection()->getClassMetadata($parent)->getExportableFormat();
                    $query = $this->conn->export->createTableSql($data['tableName'], $data['columns'], $data['options']);
                    $sql = array_merge($sql, (array) $query);
                    $finishedClasses[] = $parent;
                }
            }
            
            $data = $classMetadata->getExportableFormat();
            $query = $this->conn->export->createTableSql($data['tableName'], $data['columns'], $data['options']);

            if (is_array($query)) {
                $sql = array_merge($sql, $query);
            } else {
                $sql[] = $query;
            }

            if ($classMetadata->getAttribute(Doctrine::ATTR_EXPORT) & Doctrine::EXPORT_PLUGINS) {
                $sql = array_merge($sql, $this->exportGeneratorsSql($classMetadata));
            }
        }

        $sql = array_unique($sql);

        rsort($sql);

        return $sql;
    }
}
