<?php
require_once("Batch.php");
/**
 * a collection of Doctrine_Record objects with lazy load strategy 
 * (batch load strategy with batch size 1)
 * @package     Doctrine
 * @category    Object Relational Mapping
 * @link        www.phpdoctrine.com
 * @license     http://www.opensource.org/licenses/lgpl-license.php LGPL
 * @since       1.0
 * @version     $Revision$
 * @author      Konsta Vesterinen <kvesteri@cc.hut.fi>
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

