<?php
class ForumBoard extends Doctrine_Entity {
    public static function initMetadata($metadata) {
        /*$metadata->mapField(array(
            'fieldName' => 'id',
            'id' => true,
            'type' => 'integer',
            'length' => 4
            ));
        */
        $metadata->mapColumn('id', 'integer', 4, array('primary'));
        $metadata->mapColumn('position', 'integer');
        $metadata->mapColumn('category_id', 'integer');
        $metadata->hasOne('ForumCategory as category',
                array('local' => 'category_id', 'foreign' => 'id'));
        /*       
        $metadata->mapOneToOne(array(
            'fieldName' => 'category', // optional, defaults to targetEntity
            'targetEntity' => 'ForumCategory',
            'joinColumns' => array('category_id' => 'id')
            )); 
        */       
    }
}
