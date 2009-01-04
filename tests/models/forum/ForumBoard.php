<?php

#namespace Doctrine\Tests\Models\Forum;

class ForumBoard
{
    public $id;
    public $position;
    public $category;

    public static function initMetadata($mapping)
    {
        $mapping->mapField(array(
            'fieldName' => 'id',
            'type' => 'integer',
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
