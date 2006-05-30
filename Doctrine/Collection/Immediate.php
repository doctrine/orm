<?php
Doctrine::autoload('Doctrine_Collection');
/**
 * @author      Konsta Vesterinen
 * @package     Doctrine ORM
 * @url         www.phpdoctrine.com
 * @license     LGPL
 * @version     1.0 alpha
 */
class Doctrine_Collection_Immediate extends Doctrine_Collection {
    /**
     * @param Doctrine_DQL_Parser $graph
     * @param integer $key              
     */
    public function __construct(Doctrine_Table $table) {
        parent::__construct($table);
    }
}
?>
