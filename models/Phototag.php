<?php
class Phototag extends Doctrine_Record {
    public function setTableDefinition() {
        $this->hasColumn('photo_id', 'integer');
        $this->hasColumn('tag_id', 'integer');
    }
}
