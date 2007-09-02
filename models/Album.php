<?php
class Album extends Doctrine_Record {
    public function setUp() {
        $this->ownsMany('Song', 'Song.album_id');
    }
    public function setTableDefinition() {
        $this->hasColumn('user_id', 'integer');
        $this->hasColumn('name', 'string',20);
    }
}

