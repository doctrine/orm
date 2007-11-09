<?php
class Ticket589 extends Doctrine_Record {

    public function prepareTables(){}

    public function setTableDefinition() {
        $this->hasColumn('id', 'integer', 4, array('notnull' => true,
            'primary' => true,
            'unsigned' => true,
            'autoincrement' => true));
        $this->hasColumn('name', 'string',50);
    }
}
