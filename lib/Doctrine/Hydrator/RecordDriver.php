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
 * Doctrine_Hydrate_RecordDriver
 * Hydration strategy used for creating collections of entity objects.
 *
 * @package     Doctrine
 * @subpackage  Hydrate
 * @license     http://www.opensource.org/licenses/lgpl-license.php LGPL
 * @link        www.phpdoctrine.com
 * @since       1.0
 * @version     $Revision$
 * @author      Konsta Vesterinen <kvesteri@cc.hut.fi>
 * @author      Roman Borschel <roman@code-factory.org>
 */
class Doctrine_Hydrator_RecordDriver extends Doctrine_Locator_Injectable
{
    protected $_collections = array();
    protected $_records = array();
    protected $_mappers = array();

    public function getElementCollection($component)
    {
        $coll = new Doctrine_Collection($component);
        $this->_collections[] = $coll;

        return $coll;
    }

    public function getLastKey($coll) 
    {
        $coll->end();
        
        return $coll->key();
    }
    
    public function initRelated($record, $name)
    {
        return true;
        /*
        if ( ! is_array($record)) {
            $record[$name];
            return true;
        }
        return false;
        */
    }
    
    public function registerCollection(Doctrine_Collection $coll)
    {
        $this->_collections[] = $coll;
    }

    /**
     * isIdentifiable
     * returns whether or not a given data row is identifiable (it contains
     * all primary key fields specified in the second argument)
     *
     * @param array $row
     * @param Doctrine_Table $table
     * @return boolean
     */
    public function isIdentifiable(array $row, Doctrine_Table $table)
    {
        $primaryKeys = $table->getIdentifierColumnNames();

        if (is_array($primaryKeys)) {
            foreach ($primaryKeys as $id) {
                if ( ! isset($row[$id])) {
                    return false;
                }
            }
        } else {
            if ( ! isset($row[$primaryKeys])) {
                return false;
            }
        }
        return true;
    }
    
    public function getNullPointer() 
    {
        return self::$_null;
    }
    
    public function getElement(array $data, $component)
    {
        if ( ! isset($this->_mappers[$component])) {
            $this->_mappers[$component] = Doctrine_Manager::getInstance()->getMapper($component);
        }
        
        $component = $this->_getClassnameToReturn($data, $component);
        $record = $this->_mappers[$component]->getRecord($data);

        if ( ! isset($this->_records[$record->getOid()]) ) {
            $record->clearRelated();
            $this->_records[$record->getOid()] = $record;
        }

        return $record;
    }
    
    public function flush()
    {
        // take snapshots from all initialized collections
        foreach ($this->_collections as $key => $coll) {
            $coll->takeSnapshot();
        }
    }
    
    /**
     * Check the dataset for a discriminator column, to determine the correct
     * class to instantiate. If no discriminator column is found, the given
     * classname will be returned.
     *
     * @todo this function could use reflection to check the first time it runs
     * if the subclassing option is not set.
     *
     * @return string The name of the class to instantiate.
     *
     */
    protected function _getClassnameToReturn(array $data, $className)
    {
        $subClasses = $this->_mappers[$className]->getTable()->getOption('subclasses');
        if ( ! isset($subClasses)) {
            return $className;
        }

        foreach ($subClasses as $subclass) {
            if ( ! isset($this->_mappers[$subclass])) {
                $this->_mappers[$subclass] = Doctrine_Manager::getInstance()->getMapper($subclass);
            }
            $mapper = $this->_mappers[$subclass];
            $inheritanceMap = $mapper->getDiscriminatorColumn();
            foreach ($inheritanceMap as $key => $value) {
                if (isset($data[$key]) && $data[$key] == $value) {
                    return $mapper->getComponentName();
                }
            }
        }
        return $className;
    }
}
