<?php
class Data_File extends Doctrine_Record {
    public static function initMetadata($class) {
        $class->setColumn('filename', 'string');
        $class->setColumn('file_owner_id', 'integer');
        $class->hasOne('File_Owner', array('local' => 'file_owner_id', 'foreign' => 'id'));
    }
}
