<?php
class User extends Doctrine_Record {
    public function setTableDefinition() {
        // the name cannot contain whitespace
        $this->hasColumn("name", "string", 50, array("nospace" => true));

        // the email should be a valid email
        $this->hasColumn("email", "string", 200, array("email" => true));

        // home_country should be a valid country code and not null
        $this->hasColumn("home_country", "string", 2, array("country" => true, "notnull" => true));

    }
}
?>
