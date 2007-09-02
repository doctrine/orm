<?php
class RelationTestChild extends RelationTest 
{
    public function setUp() 
    {
        $this->hasOne('RelationTest as Parent', 'RelationTestChild.child_id');

        $this->ownsMany('RelationTestChild as Children', 'RelationTestChild.child_id');
    }
}
