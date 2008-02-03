<?php
class Policy extends Doctrine_Record
{
    public static function initMetadata($class)
    {
        $class->setColumn('policy_number', 'integer', 11, array('unique' => true));
        $class->hasMany('PolicyAsset as PolicyAssets', array('local' => 'policy_number',
                                                            'foreign' => 'policy_number'));
        $class->addIndex('policy_number_index', array('fields' => array('policy_number')));
    }
  
}
