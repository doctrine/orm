<?php
class EventListenerTest extends Doctrine_Record {
    public static function initMetadata($class) {
        $class->setColumn("name", "string", 100);
        $class->setColumn("password", "string", 8);
    }
    public function getName($name) {
        return strtoupper($name);
    }
    public function setPassword($password) {
        return md5($password);
    }
}
