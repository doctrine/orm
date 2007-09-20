<?php
/*
 *  $Id: Import.php 2552 2007-09-19 19:33:00Z Jonathan.Wage $
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
 * Doctrine_Data_Import
 *
 * @package     Doctrine
 * @author      Jonathan H. Wage <jwage@mac.com>
 * @license     http://www.opensource.org/licenses/lgpl-license.php LGPL
 * @category    Object Relational Mapping
 * @link        www.phpdoctrine.com
 * @since       1.0
 * @version     $Revision: 2552 $
 */
class Doctrine_Data_Import extends Doctrine_Data
{
    /**
     * constructor
     *
     * @param string $directory 
     * @return void
     * @author Jonathan H. Wage
     */
    
    public function __construct($directory = null)
    {
        if ($directory !== null) {
            $this->setDirectory($directory);
        }
    }
    /**
     * doImport
     *
     * @return void
     * @author Jonathan H. Wage
     */
    public function doImport()
    {
        $directory = $this->directory;
        
        $array = array();
        
        if ($directory !== null) {
            foreach ((array) $directory as $dir) {
                $e = explode('.', $dir);
                
                // If they specified a specific yml file
                if (end($e) == 'yml') {
                    $array = array_merge($array, Doctrine_Parser::load($dir, $this->getFormat()));
                // If they specified a directory
                } else if(is_dir($dir)) {
                    $it = new RecursiveIteratorIterator(new RecursiveDirectoryIterator($dir),
                                                            RecursiveIteratorIterator::LEAVES_ONLY);

                    foreach ($it as $file) {
                        $e = explode('.', $file->getFileName());
                        if (in_array(end($e), $this->getFormats())) {
                            $array = array_merge($array, Doctrine_Parser::load($file->getPathName(), $this->getFormat()));
                        }
                    }   
                }
            }
        }
        
        $this->loadData($array);
    }
    /**
     * loadData
     *
     * @param string $array 
     * @return void
     * @author Jonathan H. Wage
     */
    protected function loadData(array $array)
    {
        $specifiedModels = $this->getModels();
        
        $pendingRelations = array();
        
        $primaryKeys = array();
        
        foreach ($array as $className => $data) {
            
            if (!empty($specifiedModels) && !in_array($className, $specifiedModels)) {
                continue;
            }
            
            foreach ($data as $rowKey => $row) {
                $obj = new $className();
                
                foreach ($row as $key => $value) {
                    // If row key is a relation store it for later fixing once we have all primary keys
                    if ($obj->getTable()->hasRelation($key)) {
                        $relation = $obj->getTable()->getRelation($key);
                        
                        $pendingRelations[] = array('key' => $value, 'obj' => $obj, 'local' => $relation['local'], 'foreign' => $relation['foreign']);
                    // If we have a normal column
                    } else if ($obj->getTable()->hasColumn($key)) {
                        $obj->$key = $value;
                    // Otherwise lets move on
                    } else {
                        continue;
                    }
                }
                
                $obj->save();
                
                $primaryKeys[$rowKey] = $obj->identifier();
            }
        }
        
        foreach ($pendingRelations as $rowKey => $pending) {
            $obj = $pending['obj'];
            $key = $pending['key'];
            $local = $pending['local'];
            $foreign = $pending['foreign'];
            $pks = $primaryKeys[$key];
            $obj->$local = $pks['id'];
            
            $obj->save();
        }
    }
    /**
     * doImportDummyData
     *
     * @param string $num 
     * @return void
     * @author Jonathan H. Wage
     */
    public function doImportDummyData($num = 3)
    {
        $models = Doctrine::getLoadedModels();
        
        $specifiedModels = $this->getModels();
        
        foreach ($models as $name) {
            if (!empty($specifiedModels) && !in_array($name, $specifiedModels)) {
                continue;
            }
            
            for ($i = 0; $i < $num; $i++) {
                $obj = new $name();
                $columns = array_keys($obj->toArray());
                $pks = $obj->getTable()->getPrimaryKeys();
                
                foreach ($columns as $column) {
                    
                    if (!in_array($column, $pks)) {
                       $obj->$column = uniqid();
                    }
                }
                
                $obj->save();
                
                $ids[get_class($obj)][] = $obj->identifier();
            }
        }
    }
}