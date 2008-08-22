<?php
class ForumBoard extends Doctrine_Entity
{
    public static function initMetadata($mapping)
    {
        /*$metadata->mapField(array(
            'fieldName' => 'id',
            'id' => true,
            'type' => 'integer',
            'length' => 4
            ));
        */
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
            'fieldName' => 'category_id',
            'type' => 'integer'
        ));
               
        $mapping->mapManyToOne(array(
            'fieldName' => 'category',
            'targetEntity' => 'ForumCategory',
            'joinColumns' => array('category_id' => 'id')
        ));
    }
}
