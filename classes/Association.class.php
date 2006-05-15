<?php
require_once("Relation.class.php");
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
    public function __construct(Doctrine_Table $table, Doctrine_Table $associationTable, $local, $foreign, $type) {
        parent::__construct($table, $local, $foreign, $type);
        $this->associationTable = $associationTable;
    }
    /**
     * @return Doctrine_Table
     */
    public function getAssociationFactory() {
        return $this->associationTable;
    }
}
?>
