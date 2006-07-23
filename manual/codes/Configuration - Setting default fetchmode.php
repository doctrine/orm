<?php
// setting default fetchmode
// availible fetchmodes are Doctrine::FETCH_LAZY, Doctrine::FETCH_IMMEDIATE and Doctrine::FETCH_BATCH
// the default fetchmode is Doctrine::FETCH_LAZY

class Address extends Doctrine_Record {
    public function setUp() {
        $this->setAttribute(Doctrine::ATTR_FETCHMODE,Doctrine::FETCH_IMMEDIATE);
    }
}
?>
