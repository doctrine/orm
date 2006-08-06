<?php
class Email extends Doctrine_Record {
    public function setTableDefinition() {
        // setting custom table name:
        $this->setTableName('emails');

        $this->hasColumn("address",         // name of the column
                         "string",          // column type
                         "200",             // column length
                         "notblank|email"   // validators / constraints
                         );
    }
}
?>
