<?php
class Email extends Doctrine_Record {
    public function setTableDefinition() {
        /**
         * email table has one column named 'address' which is
         * php type 'string'
         * maximum length 200
         * database constraints: UNIQUE
         * validators: email, unique
         *
         */
        $this->hasColumn("address","string",200,"email|unique");
    }
}
?>
