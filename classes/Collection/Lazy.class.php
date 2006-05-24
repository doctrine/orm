<?php
require_once("Batch.class.php");
/**
 * a collection of Doctrine_Record objects with lazy load strategy 
 * (batch load strategy with batch size 1)
 */
class Doctrine_Collection_Lazy extends Doctrine_Collection_Batch {
    /**
     * constructor
     * @param Doctrine_DQL_Parser $graph      
     * @param string $key
     */
    public function __construct(Doctrine_Table $table) {
        parent::__construct($table);
        parent::setBatchSize(1);
    }
}
?>
