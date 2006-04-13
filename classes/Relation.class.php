<?php
/**
 * @class Doctrine_Relation
 *
 * @author      Konsta Vesterinen
 * @package     Doctrine ORM
 * @url         www.phpdoctrine.com
 * @license     LGPL
 * @version     1.0 alpha
 *
 */
class Doctrine_Relation {
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
     * @see Doctrine_Table::BIND_ONE, Doctrine_Table::BIND_MANY
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
