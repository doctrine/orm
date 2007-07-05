<?php

class Doctrine_Ticket381_TestCase extends Doctrine_UnitTestCase {

    public function prepareData() 
    { }
    public function prepareTables() {
        $this->tables = array('Book');
        parent::prepareTables();
    }
    
    public function testTicket()
    {
        $obj = new Book();
        $obj->save();
        $obj->set('name', 'yes');
        $obj->save();
        $this->assertEqual($obj->get('name'), 'yes');
        $obj->save();
    }
    public function testTicket2()
    {
        $obj = new Book();
        $obj->set('name', 'yes2');
        $obj->save();
        $this->assertEqual($obj->get('name'), 'yes2');
        $obj->save();
    }
}
