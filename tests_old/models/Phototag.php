<?php
class Phototag extends Doctrine_Entity {
    public static function initMetadata($class) {
        $class->setColumn('photo_id', 'integer');
        $class->setColumn('tag_id', 'integer');
    }
}
