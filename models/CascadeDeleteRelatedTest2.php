<?php
class CascadeDeleteRelatedTest2 extends Doctrine_Record
{
    public function setTableDefinition()
    {
        $this->hasColumn('name', 'string');
        $this->hasColumn('cscd_id', 'integer');
    }
    public function setUp()
    {
        $this->hasOne('CascadeDeleteRelatedTest', array('local' => 'cscd_id',
                                                        'foreign' => 'id',
                                                        'onDelete' => 'SET NULL'));
    }
}
