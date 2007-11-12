<?php
abstract class BaseSymfonyRecord extends Doctrine_Record
{
    public function setUp()
    {
    }

    public function setTableDefinition()
    {
        $this->hasColumn('name', 'string', 30);
    }

}
