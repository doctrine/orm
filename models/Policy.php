<?php
class Policy extends Doctrine_Record
{
    public function setTableDefinition()
    {
        $this->hasColumn('policy_number', 'integer', 11, array('unique' => true));
    }
  
    public function setUp()
    {
        $this->hasMany('PolicyAsset as PolicyAssets', array('local' => 'policy_number',
                                                            'foreign' => 'policy_number'));
        $this->index('policy_number_index', array('fields' => array('policy_number')));
    }
}
