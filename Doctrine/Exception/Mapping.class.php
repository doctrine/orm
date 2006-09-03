<?php
Doctrine::autoload("Doctrine_Exception");
/**
 * thrown when user tries to get a foreign key object but the mapping is not done right
 */
class Doctrine_Mapping_Exception extends Doctrine_Exception { 
    public function __construct($message = "An error occured in the mapping logic.") {
        parent::__construct($message,Doctrine::ERR_MAPPING);
    }
}

