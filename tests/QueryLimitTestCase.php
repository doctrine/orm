<?php
class Doctrine_Query_Limit_TestCase extends Doctrine_UnitTestCase {
    public function testLimit() {
        $this->query->from("User.Phonenumber");
        $this->query->limit(5);

        $sql = $this->query->getQuery();

        

        $users = $this->query->execute();
        $count = $this->dbh->count();
        $this->assertEqual($users->count(), 5);
        $users[0]->Phonenumber[0];
        $this->assertEqual($count, $this->dbh->count());


        $this->query->offset(2);

        $users = $this->query->execute();
        $count = $this->dbh->count();
        $this->assertEqual($users->count(), 5);
        $users[3]->Phonenumber[0];
        $this->assertEqual($count, $this->dbh->count());
    }
}
?>
