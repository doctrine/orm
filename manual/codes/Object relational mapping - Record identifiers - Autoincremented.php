<?php
class User extends Doctrine_Record {
    public function setTableDefinition() {
        $this->hasColumn("uid","integer",20,"primary|autoincrement");
    }
}
?>
