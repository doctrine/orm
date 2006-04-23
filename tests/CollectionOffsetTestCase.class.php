<?php
class Doctrine_Collection_OffsetTestCase extends Doctrine_UnitTestCase {
    public function testExpand() {

        $users = $this->session->query("FROM User-o");
        $this->assertTrue($users instanceof Doctrine_Collection_Offset);

        $this->assertEqual(count($users), 5);
        $users[5];

        $this->assertEqual($users[5]->getState(), Doctrine_Record::STATE_CLEAN);
        $users[5];

        $this->session->setAttribute(Doctrine::ATTR_COLL_LIMIT, 3);

        $users = $this->session->query("FROM User-o");
        $this->assertEqual(count($users), 3);
        $this->assertEqual($users[0]->getState(), Doctrine_Record::STATE_CLEAN);
        $this->assertEqual($users[1]->getState(), Doctrine_Record::STATE_CLEAN);
        $this->assertEqual($users[2]->getState(), Doctrine_Record::STATE_CLEAN);
        // indexes 0,1,2 in use

        $users[7];
        $this->assertEqual(count($users), 5);
        $this->assertFalse($users->contains(3));
        $this->assertFalse($users->contains(4));
        $this->assertFalse($users->contains(5));
        $this->assertTrue($users->contains(6));
        $this->assertTrue($users->contains(7));

        $this->assertEqual($users[6]->getState(), Doctrine_Record::STATE_CLEAN);
        $this->assertEqual($users[7]->getState(), Doctrine_Record::STATE_CLEAN);


        $users[5];
        $this->assertEqual(count($users), 8);
        $this->assertEqual($users[3]->getState(), Doctrine_Record::STATE_CLEAN);
        $this->assertEqual($users[4]->getState(), Doctrine_Record::STATE_CLEAN);
        $this->assertEqual($users[5]->getState(), Doctrine_Record::STATE_CLEAN);


        $this->session->setAttribute(Doctrine::ATTR_COLL_LIMIT, 1);
        $users = $this->session->query("FROM User-b, User.Phonenumber-o WHERE User.".$this->objTable->getIdentifier()." = 5");

        $this->assertEqual(count($users), 1);

        $coll = $users[0]->Phonenumber;
        $this->assertEqual(count($coll), 1);
        $coll[1];

        $this->assertEqual(count($coll), 2);
        $this->assertEqual($coll[1]->phonenumber, "456 456");

    }

    public function testGetIterator() {
        $this->session->setAttribute(Doctrine::ATTR_COLL_LIMIT, 4);
        $coll = $this->session->query("FROM User-o");

        foreach($coll as $user) {
        }
        $this->assertEqual($coll->count(), 8);
        $this->assertEqual($coll[3]->getState(), Doctrine_Record::STATE_CLEAN);
        $this->assertEqual($coll[6]->getState(), Doctrine_Record::STATE_CLEAN);

        $this->session->setAttribute(Doctrine::ATTR_COLL_LIMIT, 3);

        $coll = $this->session->query("FROM User-o");

        foreach($coll as $user) {
        }
        $this->assertEqual($coll->count(), 8);
        $this->assertEqual($coll[3]->getState(), Doctrine_Record::STATE_CLEAN);
        $this->assertEqual($coll[6]->getState(), Doctrine_Record::STATE_CLEAN);
    }
}
?>
