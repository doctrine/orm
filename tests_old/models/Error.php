<?php
class Error extends Doctrine_Record {
    public static function initMetadata($class) {
        $class->setColumn('message', 'string',200);
        $class->setColumn('code', 'integer',11);
        $class->setColumn('file_md5', 'string',32, array('primary' => true));
        $class->hasMany('Description', array('local' => 'file_md5', 'foreign' => 'file_md5'));
    }
}

