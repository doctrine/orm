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
 * Doctrine_Hydrate_Record 
 * defines a record fetching strategy for Doctrine_Hydrate
 *
 * @package     Doctrine
 * @license     http://www.opensource.org/licenses/lgpl-license.php LGPL
 * @category    Object Relational Mapping
 * @link        www.phpdoctrine.com
 * @since       1.0
 * @version     $Revision$
 * @author      Konsta Vesterinen <kvesteri@cc.hut.fi>
 */
class Doctrine_Hydrate_Record extends Doctrine_Object
{
    protected $_collections = array();
    
    protected $_records = array();
    
    protected $_tables = array();

    public function getElementCollection($component)
    {
        $coll = new Doctrine_Collection($component);
        $this->_collections[] = $coll;

        return $coll;
    }
    public function search(Doctrine_Record $record, Doctrine_Collection $coll)
    {
        return array_search($record, $coll->getData(), true);
    }
    public function initRelated($record, $name)
    {
    	if ( ! is_array($record)) {
            $record[$name];

            return true;
        }
        return false;
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
    	$primaryKeys = $table->getIdentifier();

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
    	if ( ! isset($this->_tables[$component])) {
            $this->_tables[$component] = Doctrine_Manager::getInstance()->getTable($component);
            $this->_tables[$component]->setAttribute(Doctrine::ATTR_LOAD_REFERENCES, false);
        }
        $this->_tables[$component]->setData($data);
        $record = $this->_tables[$component]->getRecord();
        $this->_records[] = $record;

        return $record;
    }
    public function flush()
    {
        // take snapshots from all initialized collections
        foreach ($this->_collections as $key => $coll) {
            $coll->takeSnapshot();
        }
        foreach ($this->_tables as $table) {
            $table->setAttribute(Doctrine::ATTR_LOAD_REFERENCES, true);
        }
    }
}
