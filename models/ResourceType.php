<?php
class ResourceType extends Doctrine_Record {
    public function setUp() {
        $this->hasMany('Resource as ResourceAlias', 'ResourceReference.resource_id');
    }
    public function setTableDefinition() {
        $this->hasColumn('type', 'string',100);
    }
}

