<?php
/**
 * Doctrine_Association    this class takes care of association mapping
 *                         (= many-to-many relationships, where the relationship is handled with an additional relational table
 *                         which holds 2 foreign keys)
 *
 *
 * @package     Doctrine ORM
 * @url         www.phpdoctrine.com
 * @license     LGPL
 */
class Doctrine_Association extends Doctrine_Relation {
    /**
     * @var Doctrine_Table $associationTable
     */
    private $associationTable;
    /**
     * the constructor
     * @param Doctrine_Table $table                 foreign factory object
     * @param Doctrine_Table $associationTable      factory which handles the association
     * @param string $local                         local field name
     * @param string $foreign                       foreign field name
     * @param integer $type                         type of relation
     * @see Doctrine_Table constants
     */
    public function __construct(Doctrine_Table $table, Doctrine_Table $associationTable, $local, $foreign, $type, $alias) {
        parent::__construct($table, $local, $foreign, $type, $alias);
        $this->associationTable = $associationTable;
    }
    /**
     * @return Doctrine_Table
     */
    public function getAssociationFactory() {
        return $this->associationTable;
    }
    /**
     * getRelationDql
     *
     * @param integer $count
     * @return string
     */
    public function getRelationDql($count, $context = 'record') {
        $sub    = "SELECT ".$this->foreign.
                  " FROM ".$this->associationTable->getTableName().
                  " WHERE ".$this->local.
                  " IN (".substr(str_repeat("?, ", $count),0,-2).")";

        $dql    = "FROM ".$this->table->getComponentName();
        
        if($context != 'record')
            $dql .= ":".$this->associationTable->getComponentName();

        $dql   .= " WHERE ".$this->table->getComponentName().".".$this->table->getIdentifier().
                  " IN ($sub)";

        return $dql;
    }
}

