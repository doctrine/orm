<?php
class Song extends Doctrine_Record {
    public function setTableDefinition() {
        $this->hasColumn('album_id', 'integer');
        $this->hasColumn('genre', 'string',20);
        $this->hasColumn('title', 'string',30);
    }
}
