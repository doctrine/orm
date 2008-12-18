<?php
class ForumCategory
{
    private $id;
    public $position;
    public $name;
    public $boards;

    public function getId() {
        return $this->id;
    }

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
            'fieldName' => 'name',
            'type' => 'string',
            'length' => 255
        ));
        
        $mapping->mapOneToMany(array(
            'fieldName' => 'boards',
            'targetEntity' => 'ForumBoard',
            'mappedBy' => 'category'
        ));
    }
}
