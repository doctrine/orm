<?php
/*
 *  $Id: Import.php 4866 2008-08-31 18:27:16Z romanb $
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

#namespace Doctrine::ORM::Import;

/**
 * class Doctrine_Import
 * Main responsible of performing import operation. Delegates database schema
 * reading to a reader object and passes the result to a builder object which
 * builds a Doctrine data model.
 *
 * @package     Doctrine
 * @subpackage  Import
 * @link        www.phpdoctrine.org
 * @license     http://www.opensource.org/licenses/lgpl-license.php LGPL
 * @since       1.0
 * @version     $Revision: 4866 $
 * @author      Konsta Vesterinen <kvesteri@cc.hut.fi>
 * @author      Jukka Hassinen <Jukka.Hassinen@BrainAlliance.com>
 */
class Doctrine_Import extends Doctrine_Connection_Module
{
    /**
     * importSchema
     *
     * method for importing existing schema to Doctrine_Entity classes
     *
     * @param string $directory
     * @param array $databases
     * @return array                the names of the imported classes
     * @todo ORM stuff
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
          foreach ($connection->getSchemaManager()->listTables() as $table) {
              $definition = array();
              $definition['tableName'] = $table;
              $definition['className'] = Doctrine_Inflector::classify($table);
              $definition['columns'] = $connection->getSchemaManager()->listTableColumns($table);
              
              $builder->buildRecord($definition);
        
              $classes[] = $definition['className'];
          }
        }
        
        return $classes;
    }
}