<?php

declare(strict_types=1);

namespace Doctrine\Tests\ORM\Functional\Ticket;

use Doctrine\Tests\Models\FieldColumnConflict\User;
use Doctrine\Tests\Models\FieldColumnConflict\UserContent;
use Doctrine\Tests\OrmFunctionalTestCase;

class FieldColumnConflictTest extends OrmFunctionalTestCase
{
    protected function setUp(): void
    {
        parent::setUp();

        $this->createSchemaForModels(User::class, UserContent::class);
    }

    public function testIssue(): void
    {
        $user     = new User();
        $user->id = 1;

        $this->_em->persist($user);

        $userContent       = new UserContent();
        $userContent->id   = 'uuid';
        $userContent->data = 'data';

        $this->_em->persist($userContent);

        $user->userContent = $userContent;

        $this->_em->flush();
        $this->_em->clear();

        $user = $this->_em->find(User::class, 1);
        self::assertSame(1, $user->id);
        self::assertNotNull($user->userContent);
        self::assertSame('uuid', $user->userContent->id);
    }
}
