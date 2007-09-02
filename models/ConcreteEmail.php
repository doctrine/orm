<?php
class ConcreteEmail extends Doctrine_Record
{
    public function setUp()
    {
        $this->loadTemplate('EmailTemplate');
    }
}
