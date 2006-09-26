<?php
Doctrine::autoload('Doctrine_Exception');
/**
 * thrown when user tries to initialize a new instance of Doctrine_Table,
 * while there already exists an instance of that table
 */
class Doctrine_Table_Exception extends Doctrine_Exception {
    public function __construct($message = "Couldn't initialize table. One instance of this
                            table already exists. Always use Doctrine_Session::getTable(\$name)
                            to get on instance of a Doctrine_Table.") {
        parent::__construct($message,Doctrine::ERR_TABLE_INSTANCE);
    }
}

