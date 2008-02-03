<?php
class ValidatorTest extends Doctrine_Record {
    public static function initMetadata($class) {
        $class->setColumn('mymixed', 'string', 100);
        $class->setColumn('mystring', 'string', 100, array('notnull', 'unique'));
        $class->setColumn('myarray', 'array', 1000);
        $class->setColumn('myobject', 'object', 1000);
        $class->setColumn('myinteger', 'integer', 11);
        $class->setColumn('myrange', 'integer', 11, array('range' => array(4,123)));
        $class->setColumn('myregexp', 'string', 5, array('regexp' => '/^[0-9]+$/'));

        $class->setColumn('myemail', 'string', 100, array('email'));
        $class->setColumn('myemail2', 'string', 100, array('email', 'notblank'));
    }
}
