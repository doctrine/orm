<?php
class Doctrine_CollectionTestCase extends Doctrine_UnitTestCase {
    public function testAdd() {
        $coll = new Doctrine_Collection($this->objTable);
        $coll->add(new User());
        $this->assertEqual($coll->count(),1);
        $coll->add(new User());
        $this->assertTrue($coll->count(),2);

        $this->assertEqual($coll->getKeys(), array(0,1));

        $coll[2] = new User();

        $this->assertTrue($coll->count(),3);
        $this->assertEqual($coll->getKeys(), array(0,1,2));
    }

    public function testExpand() {
        $users = $this->session->query("FROM User-b.Phonenumber-l WHERE User.Phonenumber.phonenumber LIKE '%123%'");

        $this->assertTrue($users instanceof Doctrine_Collection_Batch);
        $this->assertTrue($users[1] instanceof User);
        $this->assertTrue($users[1]->Phonenumber instanceof Doctrine_Collection_Lazy);
        $data = $users[1]->Phonenumber->getData();
        
        $coll = $users[1]->Phonenumber;

        $this->assertEqual(count($data), 1);

        foreach($coll as $record) {
            $record->phonenumber;
        }

        $coll[1];

        $this->assertEqual(count($coll), 3);

        $this->assertEqual($coll[2]->getState(), Doctrine_Record::STATE_PROXY);


        $generator = new Doctrine_IndexGenerator($this->objTable->getIdentifier());
        $coll->setGenerator($generator);
        $generator = $coll->getGenerator();
        
        $user = $this->session->getTable("User")->find(4);
        $this->assertEqual($generator->getIndex($user), 4);

    }
    public function testGenerator() {
        $generator = new Doctrine_IndexGenerator("name");
        $coll = new Doctrine_Collection($this->objTable);
        $coll->setGenerator($generator);

        $user = new User();
        $user->name = "name";
        $coll->add($user);

        $this->assertEqual($coll["name"], $user);


        $this->session->getTable("email")->setAttribute(Doctrine::ATTR_COLL_KEY,"address");
        $emails = $this->session->getTable("email")->findAll();
        foreach($emails as $k => $v) {
            $this->assertTrue(gettype($k), "string");
        }

    }
}
?>
