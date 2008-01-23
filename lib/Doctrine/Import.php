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
Doctrine::autoload('Doctrine_Connection_Module');
/**
 * class Doctrine_Import
 * Main responsible of performing import operation. Delegates database schema
 * reading to a reader object and passes the result to a builder object which
 * builds a Doctrine data model.
 *
 * @package     Doctrine
 * @subpackage  Import
 * @link        www.phpdoctrine.com
 * @license     http://www.opensource.org/licenses/lgpl-license.php LGPL
 * @since       1.0
 * @version     $Revision$
 * @author      Konsta Vesterinen <kvesteri@cc.hut.fi>
 * @author      Jukka Hassinen <Jukka.Hassinen@BrainAlliance.com>
 */
class Doctrine_Import extends Doctrine_Connection_Module
{
    protected $sql = array();

    /**
     * lists all databases
     *
     * @return array
     */
    public function listDatabases()
    {
        if ( ! isset($this->sql['listDatabases'])) {
            throw new Doctrine_Import_Exception(__FUNCTION__ . ' not supported by this driver.');
        }

        return $this->conn->fetchColumn($this->sql['listDatabases']);
    }

    /**
     * lists all availible database functions
     *
     * @return array
     */
    public function listFunctions()
    {
        if ( ! isset($this->sql['listFunctions'])) {
            throw new Doctrine_Import_Exception(__FUNCTION__ . ' not supported by this driver.');
        }

        return $this->conn->fetchColumn($this->sql['listFunctions']);
    }

    /**
     * lists all database triggers
     *
     * @param string|null $database
     * @return array
     */
    public function listTriggers($database = null)
    {
        throw new Doctrine_Import_Exception(__FUNCTION__ . ' not supported by this driver.');
    }

    /**
     * lists all database sequences
     *
     * @param string|null $database
     * @return array
     */
    public function listSequences($database = null)
    {
        if ( ! isset($this->sql['listSequences'])) {
            throw new Doctrine_Import_Exception(__FUNCTION__ . ' not supported by this driver.');
        }

        return $this->conn->fetchColumn($this->sql['listSequences']);
    }

    /**
     * lists table constraints
     *
     * @param string $table     database table name
     * @return array
     */
    public function listTableConstraints($table)
    {
        throw new Doctrine_Import_Exception(__FUNCTION__ . ' not supported by this driver.');
    }

    /**
     * lists table constraints
     *
     * @param string $table     database table name
     * @return array
     */
    public function listTableColumns($table)
    {
        throw new Doctrine_Import_Exception(__FUNCTION__ . ' not supported by this driver.');
    }

    /**
     * lists table constraints
     *
     * @param string $table     database table name
     * @return array
     */
    public function listTableIndexes($table)
    {
        throw new Doctrine_Import_Exception(__FUNCTION__ . ' not supported by this driver.');
    }

    /**
     * lists tables
     *
     * @param string|null $database
     * @return array
     */
    public function listTables($database = null)
    {
        throw new Doctrine_Import_Exception(__FUNCTION__ . ' not supported by this driver.');
    }

    /**
     * lists table triggers
     *
     * @param string $table     database table name
     * @return array
     */
    public function listTableTriggers($table)
    {
        throw new Doctrine_Import_Exception(__FUNCTION__ . ' not supported by this driver.');
    }

    /**
     * lists table views
     *
     * @param string $table     database table name
     * @return array
     */
    public function listTableViews($table)
    {
        throw new Doctrine_Import_Exception(__FUNCTION__ . ' not supported by this driver.');
    }

    /**
     * lists database users
     *
     * @return array
     */
    public function listUsers()
    {
        if ( ! isset($this->sql['listUsers'])) {
            throw new Doctrine_Import_Exception(__FUNCTION__ . ' not supported by this driver.');
        }

        return $this->conn->fetchColumn($this->sql['listUsers']);
    }

    /**
     * lists database views
     *
     * @param string|null $database
     * @return array
     */
    public function listViews($database = null)
    {
        if ( ! isset($this->sql['listViews'])) {
            throw new Doctrine_Import_Exception(__FUNCTION__ . ' not supported by this driver.');
        }

        return $this->conn->fetchColumn($this->sql['listViews']);
    }

    /**
     * importSchema
     *
     * method for importing existing schema to Doctrine_Record classes
     *
     * @param string $directory
     * @param array $databases
     * @return array                the names of the imported classes
     */
    public function importSchema($directory, array $databases = array(), array $options = array())
    {
        $connections = Doctrine_Manager::getInstance()->getConnections();
        
        foreach ($connections as $name => $connection) {
          // Limit the databases to the ones specified by $databases.
          // Check only happens if array is not empty
          if ( ! empty($databases) && !in_array($name, $databases)) {
            continue;
          }
          
          $builder = new Doctrine_Builder_Record();
          $builder->setTargetPath($directory);
          $builder->setOptions($options);

          $classes = array();
          foreach ($connection->import->listTables() as $table) {
              $definition = array();
              $definition['tableName'] = $table;
              $definition['className'] = Doctrine_Inflector::classify($table);
              $definition['columns'] = $connection->import->listTableColumns($table);
              
              $builder->buildRecord($definition);
        
              $classes[] = $definition['className'];
          }
        }
        
        return $classes;
    }
}