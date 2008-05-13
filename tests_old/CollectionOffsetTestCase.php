<?php
class Doctrine_Collection_Offset_TestCase extends Doctrine_UnitTestCase {
    public function testExpand() {

        $users = $this->connection->query("FROM User-o");
        $this->assertTrue($users instanceof Doctrine_Collection_Offset);

        $this->assertEqual(count($users), 5);
        $users[5];

        $this->assertEqual($users[5]->getState(), Doctrine_Entity::STATE_CLEAN);
        $users[5];

        $this->connection->setAttribute(Doctrine::ATTR_COLL_LIMIT, 3);

        $users = $this->connection->query("FROM User-o");
        $this->assertEqual(count($users), 3);
        $this->assertEqual($users[0]->getState(), Doctrine_Entity::STATE_CLEAN);
        $this->assertEqual($users[1]->getState(), Doctrine_Entity::STATE_CLEAN);
        $this->assertEqual($users[2]->getState(), Doctrine_Entity::STATE_CLEAN);
        // indexes 0,1,2 in use

        $users[7];
        $this->assertEqual(count($users), 5);
        $this->assertFalse($users->contains(3));
        $this->assertFalse($users->contains(4));
        $this->assertFalse($users->contains(5));
        $this->assertTrue($users->contains(6));
        $this->assertTrue($users->contains(7));

        $this->assertEqual($users[6]->getState(), Doctrine_Entity::STATE_CLEAN);
        $this->assertEqual($users[7]->getState(), Doctrine_Entity::STATE_CLEAN);


        $users[5];
        $this->assertEqual(count($users), 8);
        $this->assertEqual($users[3]->getState(), Doctrine_Entity::STATE_CLEAN);
        $this->assertEqual($users[4]->getState(), Doctrine_Entity::STATE_CLEAN);
        $this->assertEqual($users[5]->getState(), Doctrine_Entity::STATE_CLEAN);


        $this->connection->setAttribute(Doctrine::ATTR_COLL_LIMIT, 1);
        $idFieldNames = (array)$this->objTable->getIdentifier();
        $users = $this->connection->query("FROM User-b, User.Phonenumber-o WHERE User.".$idFieldNames[0]." = 5");

        $this->assertEqual(count($users), 1);

        $coll = $users[0]->Phonenumber;
        $this->assertEqual(count($coll), 3);
        $coll[1];

        $this->assertEqual(count($coll), 3);
        $this->assertEqual($coll[1]->phonenumber, "456 456");

    }

    public function testGetIterator() {
        $this->connection->setAttribute(Doctrine::ATTR_COLL_LIMIT, 4);
        $coll = $this->connection->query("FROM User-o");

        foreach($coll as $user) {
        }
        $this->assertEqual($coll->count(), 8);
        $this->assertEqual($coll[3]->getState(), Doctrine_Entity::STATE_CLEAN);
        $this->assertEqual($coll[6]->getState(), Doctrine_Entity::STATE_CLEAN);

        $this->connection->setAttribute(Doctrine::ATTR_COLL_LIMIT, 3);

        $coll = $this->connection->query("FROM User-o");

        foreach($coll as $user) {
        }
        $this->assertEqual($coll->count(), 8);
        $this->assertEqual($coll[3]->getState(), Doctrine_Entity::STATE_CLEAN);
        $this->assertEqual($coll[6]->getState(), Doctrine_Entity::STATE_CLEAN);
    }
}
