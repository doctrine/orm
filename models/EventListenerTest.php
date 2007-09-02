<?php
class EventListenerTest extends Doctrine_Record {
    public function setTableDefinition() {
        $this->hasColumn("name", "string", 100);
        $this->hasColumn("password", "string", 8);
    }
    public function setUp() {
        //$this->attribute(Doctrine::ATTR_LISTENER, new Doctrine_EventListener_AccessorInvoker());
    }
    public function getName($name) {
        return strtoupper($name);
    }
    public function setPassword($password) {
        return md5($password);
    }
}
