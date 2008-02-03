<?php
class EventListenerChainTest extends Doctrine_Record 
{
    public static function initMetadata($class) {
        $class->setColumn('name', 'string', 100);
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
