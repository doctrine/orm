<?php
class GzipTest extends Doctrine_Record {
    public function setTableDefinition() {
        $this->hasColumn('gzip', 'gzip', 100000);
    }
}
