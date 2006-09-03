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
 * Doctrine_Relation
 * This class represents a relation between components
 *
 * @package     Doctrine ORM
 * @url         www.phpdoctrine.com
 * @license     LGPL
 */
class Doctrine_Relation {
    /**
     * RELATION CONSTANTS
     */

    /**
     * constant for ONE_TO_ONE and MANY_TO_ONE aggregate relationships
     */
    const ONE_AGGREGATE         = 0;
    /**
     * constant for ONE_TO_ONE and MANY_TO_ONE composite relationships
     */
    const ONE_COMPOSITE         = 1;
    /**
     * constant for MANY_TO_MANY and ONE_TO_MANY aggregate relationships
     */
    const MANY_AGGREGATE        = 2;
    /**
     * constant for MANY_TO_MANY and ONE_TO_MANY composite relationships
     */
    const MANY_COMPOSITE        = 3;
    

    /**
     * @var Doctrine_Table $table   foreign factory
     */
    protected $table;
    /**
     * @var string $local           local field
     */
    protected $local;
    /**
     * @var string $foreign         foreign field
     */
    protected $foreign;
    /**
     * @var integer $type           bind type
     */
    protected $type;
    /**
     * @var string $alias           relation alias
     */
    protected $alias;

    /**
     * @param Doctrine_Table $table
     * @param string $local
     * @param string $foreign
     * @param integer $type
     * @param string $alias
     */
    public function __construct(Doctrine_Table $table, $local, $foreign, $type, $alias) {
        $this->table    = $table;
        $this->local    = $local;
        $this->foreign  = $foreign;
        $this->type     = $type;
        $this->alias    = $alias;
    }
    /**
     * @return string                   the relation alias
     */
    final public function getAlias() {
        return $this->alias;
    }
    /**
     * @return integer                  the relation type, either 0 or 1
     */
    final public function getType() {
        return $this->type;
    }
    /**
     * @return object Doctrine_Table    foreign factory object
     */
    final public function getTable() {
        return $this->table;
    }
    /**
     * @return string                   the name of the local column
     */
    final public function getLocal() {
        return $this->local;
    }
    /**
     * @return string                   the name of the foreignkey column where
     *                                  the localkey column is pointing at
     */
    final public function getForeign() {
        return $this->foreign;
    }
    /** 
     * getRelationDql
     *
     * @param integer $count
     * @return string
     */
    public function getRelationDql($count) {
        $dql  = "FROM ".$this->table->getComponentName().
                " WHERE ".$this->table->getComponentName(). '.' . $this->foreign.
                " IN (".substr(str_repeat("?, ", $count),0,-2).")";
        
        return $dql;
    }
    /**
     * getDeleteOperations
     *
     * get the records that need to be deleted in order to change the old collection
     * to the new one
     *
     * The algorithm here is very simple and definitely not
     * the fastest one, since we have to iterate through the collections twice.
     * the complexity of this algorithm is O(n^2)
     *
     * We iterate through the old collection and get the records
     * that do not exists in the new collection (Doctrine_Records that need to be deleted).
     */
    final public static function getDeleteOperations(Doctrine_Collection $old, Doctrine_Collection $new) {
        $r = array();

        foreach($old as $k => $record) {
            $id = $record->getIncremented();

            if(empty($id))
                continue;

            $found = false;
            foreach($new as $k2 => $record2) {
                if($record2->getIncremented() === $record->getIncremented()) {
                    $found = true;
                    break;
                }
            }

            if( ! $found)  {
                $r[] = $record;
                unset($old[$k]);
            }
        }

        return $r;
    }
    /**
     * getInsertOperations
     *
     * get the records that need to be added in order to change the old collection
     * to the new one
     *
     * The algorithm here is very simple and definitely not
     * the fastest one, since we have to iterate through the collections twice.
     * the complexity of this algorithm is O(n^2)
     *
     * We iterate through the old collection and get the records
     * that exists only in the new collection (Doctrine_Records that need to be added).
     */
    final public static function getInsertOperations(Doctrine_Collection $old, Doctrine_Collection $new) {
        $r = array();

        foreach($new as $k => $record) {
            $found = false;

            $id = $record->getIncremented();
            if( ! empty($id)) {
                foreach($old as $k2 => $record2) {
                    if($record2->getIncremented() === $record->getIncremented()) {
                        $found = true;
                        break;
                    }
                }
            }
            if( ! $found) {
                $old[] = $record;
                $r[] = $record;
            }
        }

        return $r;
    }
    /**
     * __toString
     *
     * @return string
     */
    public function __toString() {
        $r[] = "<pre>";
        $r[] = "Class       : ".get_class($this);
        $r[] = "Component   : ".$this->table->getComponentName();
        $r[] = "Table       : ".$this->table->getTableName();
        $r[] = "Local key   : ".$this->local;
        $r[] = "Foreign key : ".$this->foreign;
        $r[] = "Type        : ".$this->type;
        $r[] = "</pre>";
        return implode("\n", $r);
    }
}


