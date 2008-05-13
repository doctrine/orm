<?php
/*
 * $Id: Schema.php 1838 2007-06-26 00:58:21Z nicobn $
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

/**
 * Doctrine_Export_Schema
 * 
 * Used for exporting a schema to a yaml file
 *
 * @package     Doctrine
 * @subpackage  Export
 * @link        www.phpdoctrine.org
 * @license     http://www.opensource.org/licenses/lgpl-license.php LGPL
 * @version     $Revision: 1838 $
 * @author      Nicolas Bérard-Nault <nicobn@gmail.com>
 * @author      Jonathan H. Wage <jwage@mac.com>
 */
class Doctrine_Export_Schema
{    
    /**
     * buildSchema
     * 
     * Build schema array that can be dumped to file
     *
     * @param string $directory 
     * @return void
     */
    public function buildSchema($directory = null, $models = array())
    {
        if ($directory) {
            $loadedModels = Doctrine::loadModels($directory);
        } else {
            $loadedModels = Doctrine::getLoadedModels();
        }

        $array = array();
        
        $parent = new ReflectionClass('Doctrine_Entity');

        $sql = array();
        $fks = array();

        // we iterate trhough the diff of previously declared classes
        // and currently declared classes
        foreach ($loadedModels as $className) {
            if ( ! empty($models) && !in_array($className, $models)) {
                continue;
            }

            $record = new $className();
            $recordTable  = $record->getTable();
            
            $data = $recordTable->getExportableFormat();
            
            $table = array();
            
            foreach ($data['columns'] AS $name => $column) {
                $data['columns'][$name]['type'] = $column['type'] . '(' . $column['length'] . ')';
                unset($data['columns'][$name]['length']);
            }
            
            $table['columns'] = $data['columns'];
            
            $relations = $recordTable->getRelations();
            foreach ($relations as $key => $relation) {
                $relationData = $relation->toArray();
                
                $relationKey = $relationData['alias'];
                
                if (isset($relationData['refTable']) && $relationData['refTable']) {
                    $table['relations'][$relationKey]['refClass'] = $relationData['refTable']->getComponentName();
                }
                
                if (isset($relationData['class']) && $relationData['class'] && $relation['class'] != $relationKey) {
                    $table['relations'][$relationKey]['class'] = $relationData['class'];
                }
 
                $table['relations'][$relationKey]['local'] = $relationData['local'];
                $table['relations'][$relationKey]['foreign'] = $relationData['foreign'];
                
                if ($relationData['type'] === Doctrine_Relation::ONE) {
                    $table['relations'][$relationKey]['type'] = 'one';
                } else if($relationData['type'] === Doctrine_Relation::MANY) {
                    $table['relations'][$relationKey]['type'] = 'many';
                } else {
                    $table['relations'][$relationKey]['type'] = 'one';
                }
            }
            
            $array[$className] = $table;
        }
        
        return $array;
    }

    /**
     * exportSchema
     *
     * @param  string $schema 
     * @param  string $directory 
     * @return string $string of data in the specified format
     * @return void
     */
    public function exportSchema($schema, $format = 'yml', $directory = null, $models = array())
    {
        $array = $this->buildSchema($directory, $models);
        
        if (is_dir($schema)) {
          $schema = $schema . DIRECTORY_SEPARATOR . 'schema.' . $format;
        }
        
        return Doctrine_Parser::dump($array, $format, $schema);
    }
}