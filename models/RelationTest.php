<?php
class RelationTest extends Doctrine_Record 
{
    public function setTableDefinition() 
    {
        $this->hasColumn('name', 'string', 200);
        $this->hasColumn('parent_id', 'integer');
    }
}

class RelationTestChild extends RelationTest 
{
    public function setUp() 
    {
        $this->hasOne('RelationTest as Parent', array(
            'local' => 'parent_id',
            'foreign' => 'id',
            'onDelete' => 'CASCADE',
        ));
        $this->hasMany('RelationTestChild as Children', array(
            'local' => 'id',
            'foreign' => 'parent_id',
        ));
    }
}
