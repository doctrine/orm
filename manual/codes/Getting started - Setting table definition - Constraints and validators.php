<?php
class User extends Doctrine_Record {
    public function setTableDefinition() {
        // the name cannot contain whitespace
        $this->hasColumn("name", "string", 50, "nospace");
        
        // the email should be a valid email
        $this->hasColumn("email", "string", 200, "email");
        
        // home_country should be a valid country code
        $this->hasColumn("home_country", "string", 2, "country");
        
    }
}
?>
