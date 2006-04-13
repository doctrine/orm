<?php
/**
 * thrown when user tries to get a foreign key object but the mapping is not done right
 */
class Doctrine_Mapping_Exception extends Doctrine_Exception { 
    public function __construct() {
        parent::__construct("An error occured in the mapping logic.",Doctrine::ERR_MAPPING);
    }
}
?>
