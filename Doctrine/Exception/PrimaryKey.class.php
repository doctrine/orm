<?php
Doctrine::autoload("Doctrine_Exception");
/**
 * thrown when Doctrine_Record is loaded and there is no primary key field
 */
class Doctrine_PrimaryKey_Exception extends Doctrine_Exception {
    public function __construct() {
        parent::__construct("No primary key column found. Each data set must have primary key column.", Doctrine::ERR_NO_PK);
    }
}

