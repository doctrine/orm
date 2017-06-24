<?php

declare(strict_types=1);

namespace Doctrine\Tests\ORM\Entity;

use Doctrine\Tests\OrmTestCase;

class ConstructorTest extends OrmTestCase
{
    public function testFieldInitializationInConstructor()
    {
        $entity = new ConstructorTestEntity1("romanb");
        self::assertEquals("romanb", $entity->username);
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

