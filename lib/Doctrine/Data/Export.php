<?php
/*
 *  $Id: Export.php 2552 2007-09-19 19:33:00Z Jonathan.Wage $
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
 * Doctrine_Data_Export
 *
 * @package     Doctrine
 * @subpackage  Data
 * @author      Jonathan H. Wage <jwage@mac.com>
 * @license     http://www.opensource.org/licenses/lgpl-license.php LGPL
 * @link        www.phpdoctrine.com
 * @since       1.0
 * @version     $Revision: 2552 $
 */
class Doctrine_Data_Export extends Doctrine_Data
{
    /**
     * constructor
     *
     * @param string $directory 
     * @return void
     * @author Jonathan H. Wage
     */
    public function __construct($directory)
    {
        $this->setDirectory($directory);
    }
    /**
     * doExport
     *
     * @return void
     * @author Jonathan H. Wage
     */
    public function doExport()
    {
        $models = Doctrine::getLoadedModels();
        $specifiedModels = $this->getModels();
        
        $data = array();
        
        $outputAll = true;
        
        foreach ($models AS $name) {
            
            if (!empty($specifiedModels) AND !in_array($name, $specifiedModels)) {
                continue;
            }
            
            $class = new $name();
            $table = $class->getTable();
            $result = $table->findAll();
            
            if (!empty($result)) {
                $data[$name] = $result;
            }
        }
        
        $data = $this->prepareData($data);
        
        return $this->dumpData($data);
    }
    /**
     * dumpData
     *
     * Dump the prepared data to the fixtures files
     *
     * @param string $array 
     * @return void
     * @author Jonathan H. Wage
     */
    public function dumpData(array $data)
    {
        $directory = $this->getDirectory();
        $format = $this->getFormat();
        
        if ($this->exportIndividualFiles()) {
            
            if (is_array($directory)) {
                throw new Doctrine_Data_Exception('You must specify a single path to a folder in order to export individual files.');
            }
            
            foreach ($data as $className => $classData) {
                Doctrine_Parser::dump($classData->toArray(), $format, $directory.DIRECTORY_SEPARATOR.$className.'.'.$format);
            }
        } else {
            if (is_dir($directory)) {
                throw new Doctrine_Data_Exception('You must specify the path to a '.$format.' file to export. You specified a directory.');
            }
            
            return Doctrine_Parser::dump($data, $format, $directory);
        }
    }
    /**
     * prepareData
     *
     * Prepare the raw data to be exported with the parser
     *
     * @param string $data 
     * @return array
     * @author Jonathan H. Wage
     */
    public function prepareData($data)
    {
        $preparedData = array();
        
        foreach ($data AS $className => $classData) {
            
            foreach ($classData as $record) {
                $className = get_class($record);
                $recordKey = $className . '_' . implode('_', $record->identifier());
                
                $recordData = $record->toArray();
                
                foreach ($recordData as $key => $value) {
                    if (!$value) {
                        continue;
                    }
                    
                    if ($relation = $this->isRelation($record, $key)) {
                        $relationAlias = $relation['alias'];
                        $relationRecord = $record->$relationAlias;
                        
                        // If collection then get first so we have an instance of the related record
                        if ($relationRecord instanceof Doctrine_Collection) {
                            $relationRecord = $relationRecord->getFirst();
                        }
                        
                        // If relation is null or does not exist then continue
                        if ($relationRecord instanceof Doctrine_Null || !$relationRecord) {
                            continue;
                        }
                        
                        // Get class name for relation
                        $relationClassName = get_class($relationRecord);
                        
                        $relationValue = $relationClassName . '_' . $value;
                        
                        $preparedData[$className][$recordKey][$relationClassName] = $relationValue;
                    } else {
                        // skip single primary keys, we need to maintain composite primary keys
                        $keys = $record->getTable()->getPrimaryKeys();
                        
                        if (count($keys) <= 1 && in_array($key, $keys)) {
                            continue;
                        }
                        
                        $preparedData[$className][$recordKey][$key] = $value;
                    }
                }
            }
        }
        
        return $preparedData;
    }
}