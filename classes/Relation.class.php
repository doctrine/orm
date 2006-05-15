<?php
/**
 * Doctrine_Relation
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
     * @var Doctrine_Table $table     foreign factory
     */
    private $table;
    /**
     * @var string $local           local field
     */
    private $local;
    /**
     * @var string $foreign         foreign field
     */
    private $foreign;
    /**
     * @var integer $type           bind type
     */
    private $type;
    /**
     * @param Doctrine_Table $table
     * @param string $local
     * @param string $foreign
     * @param integer $type
     */
    public function __construct(Doctrine_Table $table,$local,$foreign,$type) {
        $this->table    = $table;
        $this->local    = $local;
        $this->foreign  = $foreign;
        $this->type     = $type;
    }
    /**
     * @return integer              bind type 1 or 0
     */
    public function getType() {
        return $this->type;
    }
    /**
     * @return object Doctrine_Table    foreign factory object
     */
    public function getTable() {
        return $this->table;
    }
    /**
     * @return string               the name of the local column
     */
    public function getLocal() {
        return $this->local;
    }
    /**
     * @return string               the name of the foreign column where
     *                              the local column is pointing at
     */
    public function getForeign() {
        return $this->foreign;
    }
    /**
     * __toString
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

?>
