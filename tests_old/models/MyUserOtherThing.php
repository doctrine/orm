<?php
class MyUserOtherThing extends Doctrine_Record {
    public function setTableDefinition() {
        $this->hasColumn('user_id', 'integer');
        $this->hasColumn('other_thing_id', 'integer');
    }
}
