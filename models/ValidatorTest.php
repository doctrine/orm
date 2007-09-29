<?php
class ValidatorTest extends Doctrine_Record {
    public function setTableDefinition() {
        $this->hasColumn('mymixed', 'string', 100);
        $this->hasColumn('mystring', 'string', 100, array('notnull', 'unique'));
        $this->hasColumn('myarray', 'array', 1000);
        $this->hasColumn('myobject', 'object', 1000);
        $this->hasColumn('myinteger', 'integer', 11);
        $this->hasColumn('myrange', 'integer', 11, array('range' => array(4,123)));
        $this->hasColumn('myregexp', 'string', 5, array('regexp' => '/^[0-9]+$/'));

        $this->hasColumn('myemail', 'string', 100, array('email'));
        $this->hasColumn('myemail2', 'string', 100, array('email', 'notblank'));
    }
}
