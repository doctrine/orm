<?php

declare(strict_types=1);

namespace Doctrine\Tests\ORM\Entity;

use Doctrine\Tests\OrmTestCase;

class ConstructorTest extends OrmTestCase
{
    public function testFieldInitializationInConstructor(): void
    {
        $entity = new ConstructorTestEntity1('romanb');
        self::assertEquals('romanb', $entity->username);
    }
}

class ConstructorTestEntity1
{
    /** @var int */
    private $id;

    /** @var string|null */
    public $username;

    public function __construct(?string $username = null)
    {
        if ($username !== null) {
            $this->username = $username;
        }
    }
}
