<?php
class ForumCategory extends Doctrine_Entity
{
    
    public static function initMetadata($mapping)
    {
        $mapping->mapField(array(
            'fieldName' => 'id',
            'type' => 'integer',
            'length' => 4,
            'id' => true
        ));
        $mapping->mapField(array(
            'fieldName' => 'position',
            'type' => 'integer'
        ));
        $mapping->mapField(array(
            'fieldName' => 'name',
            'type' => 'string',
            'length' => 255
        ));
        
        $mapping->hasMany('ForumBoard as boards', array(
                'local' => 'id' , 'foreign' => 'category_id')); 
    }
}
