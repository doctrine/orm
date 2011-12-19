<?php

namespace Doctrine\Tests\ORM\Entity;

require_once __DIR__ . '/../../TestInit.php';

class ConstructorTest extends \Doctrine\Tests\OrmTestCase
{
    public function testFieldInitializationInConstructor()
    {
        $entity = new ConstructorTestEntity1("romanb");
        $this->assertEquals("romanb", $entity->username);
    }
}

class ConstructorTestEntity1
{
    private $id;
    public $username;

    public function __construct($username = null)
    {
        if ($username !== null) {
            $this->username = $username;
        }
    }
}

