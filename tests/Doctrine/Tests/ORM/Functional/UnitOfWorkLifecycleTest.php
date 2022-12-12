<?php

declare(strict_types=1);

namespace Doctrine\Tests\ORM\Functional;

use Doctrine\ORM\ORMInvalidArgumentException;
use Doctrine\Tests\Models\CMS\CmsUser;
use Doctrine\Tests\OrmFunctionalTestCase;

class UnitOfWorkLifecycleTest extends OrmFunctionalTestCase
{
    protected function setUp(): void
    {
        $this->useModelSet('cms');

        parent::setUp();
    }

    public function testScheduleInsertManaged(): void
    {
        $user           = new CmsUser();
        $user->username = 'beberlei';
        $user->name     = 'Benjamin';
        $user->status   = 'active';
        $this->_em->persist($user);
        $this->_em->flush();

        $this->expectException(ORMInvalidArgumentException::class);
        $this->expectExceptionMessage('A managed+dirty entity Doctrine\Tests\Models\CMS\CmsUser');

        $this->_em->getUnitOfWork()->scheduleForInsert($user);
    }

    public function testScheduleInsertDeleted(): void
    {
        $user           = new CmsUser();
        $user->username = 'beberlei';
        $user->name     = 'Benjamin';
        $user->status   = 'active';
        $this->_em->persist($user);
        $this->_em->flush();

        $this->_em->remove($user);

        $this->expectException(ORMInvalidArgumentException::class);
        $this->expectExceptionMessage('Removed entity Doctrine\Tests\Models\CMS\CmsUser');

        $this->_em->getUnitOfWork()->scheduleForInsert($user);
    }

    public function testScheduleInsertTwice(): void
    {
        $user           = new CmsUser();
        $user->username = 'beberlei';
        $user->name     = 'Benjamin';
        $user->status   = 'active';

        $this->_em->getUnitOfWork()->scheduleForInsert($user);

        $this->expectException(ORMInvalidArgumentException::class);
        $this->expectExceptionMessage('Entity Doctrine\Tests\Models\CMS\CmsUser');

        $this->_em->getUnitOfWork()->scheduleForInsert($user);
    }

    public function testAddToIdentityMapWithoutIdentity(): void
    {
        $user = new CmsUser();

        $this->expectException(ORMInvalidArgumentException::class);
        $this->expectExceptionMessage("The given entity of type 'Doctrine\Tests\Models\CMS\CmsUser' (Doctrine\Tests\Models\CMS\CmsUser@");

        $this->_em->getUnitOfWork()->registerManaged($user, [], []);
    }

    public function testMarkReadOnlyNonManaged(): void
    {
        $user = new CmsUser();

        $this->expectException(ORMInvalidArgumentException::class);
        $this->expectExceptionMessage('Only managed entities can be marked or checked as read only. But Doctrine\Tests\Models\CMS\CmsUser@');

        $this->_em->getUnitOfWork()->markReadOnly($user);
    }
}
