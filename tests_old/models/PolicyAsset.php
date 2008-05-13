<?php
class PolicyAsset extends Doctrine_Entity 
{
    public static function initMetadata($class)
    {
        $class->setColumn('policy_number', 'integer', 11);
        $class->setColumn('value', 'float', 10, array ('notblank' => true,));
        $class->hasOne('Policy', array('foreign' => 'policy_number', 
                                      'local' => 'policy_number'));
        $class->addIndex('policy_number_index', array('fields' => array('policy_number')));
    }
}
