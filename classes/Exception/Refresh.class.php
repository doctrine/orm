<?php
require_once(Doctrine::getPath().DIRECTORY_SEPARATOR."Exception.class.php");
/**
 * thrown when Doctrine_Record is refreshed and the refreshed primary key doens't match the old one
 */
class Doctrine_Refresh_Exception extends Doctrine_Exception {
    public function __construct() {
        parent::__construct("The refreshed primary key doesn't match the 
                             one in the record memory.", Doctrine::ERR_REFRESH);
    }
}
?>
