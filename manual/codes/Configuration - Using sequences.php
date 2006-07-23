<?php
// using sequences

class User extends Doctrine_Record {
    public function setUp() {
        $this->setSequenceName("user_seq");
    }
}
?>
