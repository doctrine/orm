<?php

class CompanyEmployee
{
    #protected $id;
    #protected $salary;
    #protected $department;
    
    public static function initMetadata($mapping)
    {
        // inheritance mapping
        $mapping->setInheritanceType('joined', array(
                'discriminatorColumn' => 'dtype',
                'discriminatorMap' => array(
                        'emp' => 'CompanyEmployee',
                        'man' => 'CompanyManager')
                ));
        // register subclasses
        $mapping->setSubclasses(array('CompanyManager'));
        
        $mapping->mapField(array(
            'fieldName' => 'id',
            'type' => 'integer',
            'length' => 4,
            'id' => true,
            'idGenerator' => 'auto'
        ));
        $mapping->mapField(array(
            'fieldName' => 'salary',
            'type' => 'double'
        ));
        //TODO: make department an entity
        $mapping->mapField(array(
            'fieldName' => 'department',
            'type' => 'string'
        ));
    }
    
}

?>