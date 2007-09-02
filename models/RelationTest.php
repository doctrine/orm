<?php
class RelationTest extends Doctrine_Record 
{
    public function setTableDefinition() 
    {
        $this->hasColumn('name', 'string', 200);
        $this->hasColumn('child_id', 'integer');
    }
}

class RelationTestChild extends RelationTest 
{
    public function setUp() 
    {
        $this->hasOne('RelationTest as Parent', 'RelationTestChild.child_id');

        $this->ownsMany('RelationTestChild as Children', 'RelationTestChild.child_id');
    }
}
