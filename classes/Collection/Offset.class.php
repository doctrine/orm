<?php
require_once(Doctrine::getPath().DIRECTORY_SEPARATOR."Collection.class.php");
/**
 * Offset Collection
 */
class Doctrine_Collection_Offset extends Doctrine_Collection {
    /**
     * @var integer $limit
     */
    private $limit;
    /**
     * @param Doctrine_Table $table
     */
    public function __construct(Doctrine_Table $table) {
        parent::__construct($table);
        $this->limit = $table->getAttribute(Doctrine::ATTR_COLL_LIMIT);
    }
    /**
     * @return integer
     */
    public function getLimit() {
        return $this->limit;
    }
    /**
     * @return Doctrine_Iterator_Offset
     */
    public function getIterator() {
        return new Doctrine_Iterator_Expandable($this);
    }
}
?>
