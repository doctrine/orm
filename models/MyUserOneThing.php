<?php
class MyUserOneThing extends Doctrine_Record {
    public function setTableDefinition() {
        $this->hasColumn('user_id', 'integer');
        $this->hasColumn('one_thing_id', 'integer');
    }
}
