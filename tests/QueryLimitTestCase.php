<?php
class Doctrine_Query_Limit_TestCase extends Doctrine_UnitTestCase {
    public function testLimit() {
        $this->query->from("User.Phonenumber");
        $this->query->limit(20);
    }
}
?>
