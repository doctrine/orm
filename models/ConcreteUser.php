<?php
class ConcreteUser extends Doctrine_Record
{
    public function setUp()
    {
        $this->loadTemplate('UserTemplate');
    }
}

