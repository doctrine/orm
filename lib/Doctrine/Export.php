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
 * <http://www.phpdoctrine.org>.
 */

#namespace Doctrine::DBAL::Export;

/**
 * Doctrine_Export
 *
 * @package     Doctrine
 * @subpackage  Export
 * @author      Konsta Vesterinen <kvesteri@cc.hut.fi>
 * @author      Lukas Smith <smith@pooteeweet.org> (PEAR MDB2 library)
 * @license     http://www.opensource.org/licenses/lgpl-license.php LGPL
 * @link        www.phpdoctrine.org
 * @since       1.0
 * @version     $Revision$
 * @todo Rename to ExportManager. Subclasses: MySqlExportManager, PgSqlExportManager etc.
 */
class Doctrine_Export extends Doctrine_Connection_Module
{

    /**
     * exportSchema
     * method for exporting Doctrine_Entity classes to a schema
     *
     * if the directory parameter is given this method first iterates
     * recursively trhough the given directory in order to find any model classes
     *
     * Then it iterates through all declared classes and creates tables for the ones
     * that extend Doctrine_Entity and are not abstract classes
     *
     * @throws Doctrine_Connection_Exception    if some error other than Doctrine::ERR_ALREADY_EXISTS
     *                                          occurred during the create table operation
     * @param string $directory     optional directory parameter
     * @return void
     */
    public function exportSchema($directory = null)
    {
        if ($directory !== null) {
            $models = Doctrine::loadModels($directory);
        } else {
            $models = Doctrine::getLoadedModels();
        }

        $this->exportClasses($models);
    }

    /**
     * exportClasses
     *
     * FIXME: This method is a big huge hack. The sql needs to be executed in the correct order. I have some stupid logic to 
     * make sure they are in the right order.
     *
     * method for exporting Doctrine_Entity classes to a schema
     *
     * @throws Doctrine_Connection_Exception    if some error other than Doctrine::ERR_ALREADY_EXISTS
     *                                          occurred during the create table operation
     * @param array $classes
     * @return void
     * @todo ORM stuff
     */
    public function exportClasses(array $classes)
    { 
        $connections = array();
        foreach ($classes as $class) {
            $record = new $class();
            $connection = $record->getTable()->getConnection();
            $connectionName = Doctrine_Manager::getInstance()->getConnectionName($connection);

            if ( ! isset($connections[$connectionName])) {
                $connections[$connectionName] = array(
                    'create_tables' => array(),
                    'create_sequences' => array(),
                    'alters' => array()
                );
            }

            $sql = $this->exportClassesSql(array($class));

            // Build array of all the creates
            // We need these to happen first
            foreach ($sql as $key => $query) {
                if (strstr($query, 'CREATE TABLE')) {
                    $connections[$connectionName]['create_tables'][] = $query;

                    unset($sql[$key]);
                }

                if (strstr($query, 'CREATE SEQUENCE')) {
                    $connections[$connectionName]['create_sequences'][] = $query;

                    unset($sql[$key]);
                }
            }

            $connections[$connectionName]['alters'] = array_merge($connections[$connectionName]['alters'], $sql);
        }

        // Loop over all the sql again to merge the creates and alters in to the same array, but so that the alters are at the bottom
        $build = array();
        foreach ($connections as $connectionName => $sql) {
            $build[$connectionName] = array_merge($sql['create_tables'], $sql['create_sequences'], $sql['alters']);
        }

        foreach ($build as $connectionName => $sql) {
            $connection = Doctrine_Manager::getInstance()->getConnection($connectionName);

            $connection->beginTransaction();

            foreach ($sql as $query) {
                try {
                    $connection->exec($query);
                } catch (Doctrine_Connection_Exception $e) {
                    // we only want to silence table already exists errors
                    if ($e->getPortableCode() !== Doctrine::ERR_ALREADY_EXISTS) {
                        $connection->rollback();
                        throw new Doctrine_Export_Exception($e->getMessage() . '. Failing Query: ' . $query);
                    }
                }
            }

            $connection->commit();
        }
    }

    /**
     * exportClassesSql
     * method for exporting Doctrine_Entity classes to a schema
     *
     * @throws Doctrine_Connection_Exception    if some error other than Doctrine::ERR_ALREADY_EXISTS
     *                                          occurred during the create table operation
     * @param array $classes
     * @return void
     * @todo package:orm
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

    /**
     * fetches all generators recursively for given table
     *
     * @param Doctrine_Table $table     table object to retrieve the generators from
     * @return array                    an array of Doctrine_Record_Generator objects
     * @todo package:orm
     */
    public function getAllGenerators(Doctrine_ClassMetadata $table)
    {
        $generators = array();

        foreach ($table->getGenerators() as $name => $generator) {
            if ($generator === null) {
                continue;
            }

            $generators[] = $generator;

            $generatorTable = $generator->getTable();

            if ($generatorTable instanceof Doctrine_Table) {
                $generators = array_merge($generators, $this->getAllGenerators($generatorTable));
            }
        }

        return $generators;
    }

    /**
     * exportGeneratorsSql
     * exports plugin tables for given table
     *
     * @param Doctrine_Table $table     the table in which the generators belong to
     * @return array                    an array of sql strings
     * @todo package:orm
     */
    public function exportGeneratorsSql(Doctrine_ClassMetadata $class)
    {
    	$sql = array();
        foreach ($this->getAllGenerators($class) as $name => $generator) {
            $table = $generator->getTable();

            // Make sure plugin has a valid table
            if ($table instanceof Doctrine_Table) {
                $data = $table->getExportableFormat();
                $query = $this->conn->export->createTableSql($data['tableName'], $data['columns'], $data['options']);
                $sql = array_merge($sql, (array) $query);
            }
        }

        return $sql;
    }

    /**
     * exportSql
     * returns the sql for exporting Doctrine_Entity classes to a schema
     *
     * if the directory parameter is given this method first iterates
     * recursively trhough the given directory in order to find any model classes
     *
     * Then it iterates through all declared classes and creates tables for the ones
     * that extend Doctrine_Entity and are not abstract classes
     *
     * @throws Doctrine_Connection_Exception    if some error other than Doctrine::ERR_ALREADY_EXISTS
     *                                          occurred during the create table operation
     * @param string $directory     optional directory parameter
     * @return void
     */
    public function exportSql($directory = null)
    {
        if ($directory !== null) {
            $models = Doctrine::loadModels($directory);
        } else {
            $models = Doctrine::getLoadedModels();
        }

        return $this->exportClassesSql($models);
    }

    /**
     * exportTable
     * exports given table into database based on column and option definitions
     *
     * @throws Doctrine_Connection_Exception    if some error other than Doctrine::ERR_ALREADY_EXISTS
     *                                          occurred during the create table operation
     * @return boolean                          whether or not the export operation was successful
     *                                          false if table already existed in the database
     * @todo ORM stuff
     */
    public function exportTable(Doctrine_ClassMetadata $metadata)
    {
        /**
        TODO: maybe there should be portability option for the following check
        if ( ! Doctrine::isValidClassname($table->getOption('declaringClass')->getName())) {
            throw new Doctrine_Export_Exception('Class name not valid.');
        }
        */

        try {
            $data = $metadata->getExportableFormat();

            $this->conn->export->createTable($data['tableName'], $data['columns'], $data['options']);
        } catch (Doctrine_Connection_Exception $e) {
            // we only want to silence table already exists errors
            if ($e->getPortableCode() !== Doctrine::ERR_ALREADY_EXISTS) {
                throw $e;
            }
        }
    }
}
