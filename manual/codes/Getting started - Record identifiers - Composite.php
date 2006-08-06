<?php
class Groupuser extends Doctrine_Record {
    public function setTableDefinition() {
        $this->hasColumn("user_id", "integer" 20, "primary");
        $this->hasColumn("group_id", "integer", 20, "primary");
    }
}
?>
