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

class Rec1 extends Doctrine_Record
{
    public function setTableDefinition()
    {
        $this->hasColumn('first_name', 'string', 128, array ());
    }

    public function setUp()
    {
        $this->ownsOne('Rec2 as Account', array('local' => 'id', 'foreign' => 'user_id'));
    }
}

class Rec2  extends Doctrine_Record
{
    public function setTableDefinition()
    {
        $this->hasColumn('user_id', 'integer', 10, array (  'unique' => true,));
        $this->hasColumn('address', 'string', 150, array ());
    }

    public function setUp()
    {
        $this->ownsOne('Rec1 as User', 'Rec2.user_id');
    }

}
