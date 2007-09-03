<?php
class Doctrine_Record_Lock_TestCase extends Doctrine_UnitTestCase {
    public function prepareTables()
    {
        $this->tables[] = 'rec1';
        $this->tables[] = 'rec2';
        parent::prepareTables();
    }        
        
    public function prepareData() { }
    
    public function testDeleteRecords()
    {
        $rec1 = new Rec1();
        $rec1->first_name = 'Some name';
        $rec1->Account = new Rec2();
        $rec1->Account->address = 'Some address';
        $rec1->save();
        
        $rec1->delete();
        $this->pass();
    }
}
