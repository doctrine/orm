<?php
class User extends Doctrine_Record { 
    public function setTableDefinition() {
        // set 'user' table columns, note that
        // id column is always auto-created
        
        $this->hasColumn("name","string",30);
        $this->hasColumn("username","string",20);
        $this->hasColumn("password","string",16);
        $this->hasColumn("created","integer",11);
    }
}
?>
