<?php
class Description extends Doctrine_Record {
    public function setTableDefinition() {
        $this->hasColumn('description', 'string',3000);
        $this->hasColumn('file_md5', 'string',32);
    }
}

