<?php
/**
 * thrown when user tries to find a Doctrine_Record for given primary key and that object is not found
 */
class Doctrine_Find_Exception extends Doctrine_Exception { 
    public function __construct() {
        parent::__construct("Couldn't find Data Access Object.",Doctrine::ERR_FIND);
    }
}
?>
