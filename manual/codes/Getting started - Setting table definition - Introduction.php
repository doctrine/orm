<?php
class Email extends Doctrine_Record {
    public function setTableDefinition() {
        // setting custom table name:
        $this->setTableName('emails');

        $this->hasColumn("address",         // name of the column
                         "string",          // column type
                         "200",             // column length
                         array("notblank" => true,
                               "email"    => true  // validators / constraints
                               );
                               

        $this->hasColumn("address2",         // name of the column
                         "string",          // column type
                         "200",             // column length
                         // validators / constraints without arguments can be 
                         // specified also as as string with | separator
                         "notblank|email",
                         );
                         
        // Doctrine even supports the following format for 
        // validators / constraints which have no arguments:
        
        $this->hasColumn("address3",         // name of the column
                         "string",          // column type
                         "200",             // column length
                         array("notblank", "email"),
                         );
    }
}
?>
