<?php
require_once(Doctrine::getPath().DIRECTORY_SEPARATOR."Exception.php");
/**
 * thrown when Doctrine_Record is refreshed and the refreshed primary key doens't match the old one
 */
class Doctrine_Record_Exception extends Doctrine_Exception {
    public function __construct() {
        parent::__construct("The refreshed primary key doesn't match the 
                             one in the record memory.", Doctrine::ERR_REFRESH);
    }
}
?>
