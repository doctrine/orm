<?php
class ConcreteGroupUser extends Doctrine_Record
{
    public function setUp()
    {
        $this->loadTemplate('GroupUserTemplate');
    }
}
