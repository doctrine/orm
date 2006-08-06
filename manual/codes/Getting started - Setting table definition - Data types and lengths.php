<?php
class Article extends Doctrine_Record {
    public function setTableDefinition() {
        // few mapping examples:

        // maps into VARCHAR(100) on mysql
        $this->hasColumn("title","string",100);
        
        // maps into TEXT on mysql
        $this->hasColumn("content","string",4000);

        // maps into TINYINT on mysql
        $this->hasColumn("type","integer",1);

        // maps into INT on mysql
        $this->hasColumn("type2","integer",11);

        // maps into BIGINT on mysql
        $this->hasColumn("type3","integer",20);

        // maps into TEXT on mysql
        // (serialized and unserialized automatically by doctrine)
        $this->hasColumn("types","array",4000);

    }
}
?>
