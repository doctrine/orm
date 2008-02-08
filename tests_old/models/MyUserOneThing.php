<?php
class MyUserOneThing extends Doctrine_Record {
    public static function initMetadata($class) {
        $class->setColumn('user_id', 'integer');
        $class->setColumn('one_thing_id', 'integer');
    }
}
