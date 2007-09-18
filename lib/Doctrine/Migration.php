<?php
/*
 *  $Id: Migration.php 1080 2007-02-10 18:17:08Z jwage $
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
 * Doctrine_Migration
 *
 * this class represents a database view
 *
 * @author      Jonathan H. Wage <jwage@mac.com>
 * @package     Doctrine
 * @license     http://www.opensource.org/licenses/lgpl-license.php LGPL
 * @category    Object Relational Mapping
 * @link        www.phpdoctrine.com
 * @since       1.0
 * @version     $Revision: 1080 $
 */
class Doctrine_Migration
{
    public $changes = array('created_tables'    =>  array(),
                            'dropped_tables'    =>  array(),
                            'renamed_tables'    =>  array(),
                            'added_columns'     =>  array(),
                            'renamed_columns'   =>  array(),
                            'changed_columns'   =>  array(),
                            'removed_columns'   =>  array(),
                            'added_indexes'     =>  array(),
                            'removed_indexes'   =>  array());
    
    static public function migration($directory, $from, $to)
    {
        if ($from === $to OR $from === 0) {
            throw new Doctrine_Migration_Exception('You specified an invalid migration path. The from and to cannot be the same and from cannot be zero.');
        }
        
        $direction = $from > $to ? 'down':'up';
        
        $fromPath = $directory.DIRECTORY_SEPARATOR;
        $toPath = $directory.DIRECTORY_SEPARATOR;
        
        if ($direction === 'up') {
            for ($i = $from + 1; $i <= $to; $i++) {
                self::doDirectionStep($directory, $direction, $i);
            }
        } else {
            for ($i = $from; $i > $to; $i--) {
                self::doDirectionStep($directory, $direction, $i);
            }
        }
    }
    
    public static function doDirectionStep($directory, $direction, $num)
    {
        $className = 'Migration'.$num;
        $fileName = $className.'.class.php';
        $filePath = $directory.DIRECTORY_SEPARATOR.$fileName;
        
        if (file_exists($filePath)) {
            require_once($filePath);
            
            if (class_exists($className)) {
                $migrate = new $className();
                $migrate->migrate($direction);
            }
        }
    }
    
    public function migrate($direction)
    {
        if (method_exists($this, $direction)) {
            $this->$direction();

            $this->processChanges();
        }
    }
    
    public function processChanges()
    {
        foreach ($this->changes as $type => $changes) {
            $process = new Doctrine_Migration_Process();
            $funcName = 'process'.Doctrine::classify($type);
            $process->$funcName($changes); 
        }
    }
    
    public function addChange($type, array $change = array())
    {
        $this->changes[$type][] = $change;
    }
    
    public function createTable($tableName, array $fields = array(), array $options = array())
    {
        $options = get_defined_vars();
        
        $this->addChange('created_tables', $options);
    }
    
    public function dropTable($tableName)
    {
        $options = get_defined_vars();
        
        $this->addChange('dropped_tables', $options);
    }
    
    public function renameTable($oldTableName, $newTableName)
    {
        $options = get_defined_vars();
        
        $this->addChange('renamed_tables', $options);
    }
    
    public function addColumn($tableName, $columnName, $type, $options = array())
    {
        $options = get_defined_vars();
        
        $this->addChange('added_columns', $options);
    }
    
    public function renameColumn($tableName, $oldColumnName, $newColumnName)
    {
        $options = get_defined_vars();
        
        $this->addChange('renamed_columns', $options);
    }
    
    public function changeColumn($tableName, $columnName, $type, array $options = array())
    {
        $options = get_defined_vars();
        
        $this->addChange('changed_columns', $options);
    }
    
    public function removeColumn($tableName, $columnName)
    {
        $options = get_defined_vars();
        
        $this->addChange('removed_columns', $options);
    }
    
    public function addIndex($tableName, $indexName, array $options = array())
    {
        $options = get_defined_vars();
        
        $this->addChange('added_indexes', $options);
    }
    
    public function removeIndex($tableName, $indexName)
    {
        $options = get_defined_vars();
        
        $this->addChange('removed_indexes', $options);
    }
}