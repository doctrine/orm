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

/**
 * Doctrine_Hydrator_RecordDriver
 * Hydration strategy used for creating graphs of entity objects.
 *
 * @package     Doctrine
 * @subpackage  Hydrate
 * @license     http://www.opensource.org/licenses/lgpl-license.php LGPL
 * @link        www.phpdoctrine.org
 * @since       1.0
 * @version     $Revision$
 * @author      Konsta Vesterinen <kvesteri@cc.hut.fi>
 * @author      Roman Borschel <roman@code-factory.org>
 */
class Doctrine_Hydrator_RecordDriver
{
    /** Collections initialized by the driver */
    protected $_collections = array();
    /** Mappers */
    protected $_mappers = array();
    /** Memory for initialized relations */
    private $_initializedRelations = array();
    /** Null object */
    private $_nullObject;
    
    public function __construct()
    {
        $this->_nullObject = Doctrine_Null::$INSTANCE;
    }

    public function getElementCollection($component)
    {
        $coll = new Doctrine_Collection($component);
        $this->_collections[] = $coll;

        return $coll;
    }

    public function getLastKey($coll) 
    {
        // check needed because of mixed results
        if (is_array($coll)) {
            end($coll);
            return key($coll);
        } else {
            $coll->end();
            return $coll->key(); 
        }
    }
    
    public function initRelated(Doctrine_Record $record, $name)
    {
        if ( ! isset($this->_initializedRelations[$record->getOid()][$name])) {
            $relation = $record->getClassMetadata()->getRelation($name);
            $relatedClass = $relation->getTable();
            $coll = $this->getElementCollection($relatedClass->getClassName());
            $coll->setReference($record, $relation);
            $record[$name] = $coll;
            $this->_initializedRelations[$record->getOid()][$name] = true;
        }
    }
    
    public function registerCollection(Doctrine_Collection $coll)
    {
        $this->_collections[] = $coll;
    }
    
    public function getNullPointer() 
    {
        return $this->_nullObject;
    }
    
    public function getElement(array $data, $className)
    {
        $className = $this->_getClassnameToReturn($data, $className);
        if ( ! isset($this->_mappers[$className])) {
            $this->_mappers[$className] = Doctrine_Manager::getInstance()->getMapper($className);
        }

        $record = $this->_mappers[$className]->getRecord($data);

        return $record;
    }
    
    public function flush()
    {
        // take snapshots from all initialized collections
        foreach ($this->_collections as $coll) {
            $coll->takeSnapshot();
        }
        $this->_collections = array();
        $this->_mappers = array();
        $this->_initializedRelations = array();
    }
    
    /**
     * Check the dataset for a discriminator column to determine the correct
     * class to instantiate. If no discriminator column is found, the given
     * classname will be returned.
     *
     * @return string The name of the class to instantiate.
     * @todo Can be optimized performance-wise.
     */
    protected function _getClassnameToReturn(array $data, $className)
    {
        if ( ! isset($this->_mappers[$className])) {
            $this->_mappers[$className] = Doctrine_Manager::getInstance()->getMapper($className);
        }
        
        $discCol = $this->_mappers[$className]->getClassMetadata()->getInheritanceOption('discriminatorColumn');
        if ( ! $discCol) {
            return $className;
        }
        
        $discMap = $this->_mappers[$className]->getClassMetadata()->getInheritanceOption('discriminatorMap');
        
        if (isset($data[$discCol], $discMap[$data[$discCol]])) {
            return $discMap[$data[$discCol]];
        } else {
            return $className;
        }
    }
}
