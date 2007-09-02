<?php
class EventListenerChainTest extends Doctrine_Record 
{
    public function setTableDefinition() {
        $this->hasColumn('name', 'string', 100);
    }
    public function setUp() {
        $chain = new Doctrine_EventListener_Chain();
        $chain->add(new Doctrine_EventListener_TestA());
        $chain->add(new Doctrine_EventListener_TestB());
    }
}

class Doctrine_EventListener_TestA extends Doctrine_EventListener 
{

}
class Doctrine_EventListener_TestB extends Doctrine_EventListener 
{

}
