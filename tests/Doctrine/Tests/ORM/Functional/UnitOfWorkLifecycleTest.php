<?php

namespace Doctrine\Tests\ORM\Functional;

use Doctrine\Tests\Models\CMS\CmsUser;

class UnitOfWorkLifecycleTest extends \Doctrine\Tests\OrmFunctionalTestCase
{
    protected function setUp()
    {
        $this->useModelSet('cms');
        parent::setUp();
    }

    public function testScheduleInsertManaged()
    {
        $user = new CmsUser();
        $user->username = "beberlei";
        $user->name = "Benjamin";
        $user->status = "active";
        $this->_em->persist($user);
        $this->_em->flush();

        $this->setExpectedException("Doctrine\ORM\ORMInvalidArgumentException", "A managed+dirty entity Doctrine\Tests\Models\CMS\CmsUser");
        $this->_em->getUnitOfWork()->scheduleForInsert($user);
    }

    public function testScheduleInsertDeleted()
    {
        $user = new CmsUser();
        $user->username = "beberlei";
        $user->name = "Benjamin";
        $user->status = "active";
        $this->_em->persist($user);
        $this->_em->flush();

        $this->_em->remove($user);

        $this->setExpectedException("Doctrine\ORM\ORMInvalidArgumentException", "Removed entity Doctrine\Tests\Models\CMS\CmsUser");
        $this->_em->getUnitOfWork()->scheduleForInsert($user);
    }

    public function testScheduleInsertTwice()
    {
        $user = new CmsUser();
        $user->username = "beberlei";
        $user->name = "Benjamin";
        $user->status = "active";

        $this->_em->getUnitOfWork()->scheduleForInsert($user);

        $this->setExpectedException("Doctrine\ORM\ORMInvalidArgumentException", "Entity Doctrine\Tests\Models\CMS\CmsUser");
        $this->_em->getUnitOfWork()->scheduleForInsert($user);
    }

    public function testAddToIdentityMapWithoutIdentity()
    {
        $user = new CmsUser();

        $this->setExpectedException("Doctrine\ORM\ORMInvalidArgumentException", "The given entity of type 'Doctrine\Tests\Models\CMS\CmsUser' (Doctrine\Tests\Models\CMS\CmsUser@");
        $this->_em->getUnitOfWork()->registerManaged($user, array(), array());
    }

    public function testMarkReadOnlyNonManaged()
    {
        $user = new CmsUser();

        $this->setExpectedException("Doctrine\ORM\ORMInvalidArgumentException", "Only managed entities can be marked or checked as read only. But Doctrine\Tests\Models\CMS\CmsUser@");
        $this->_em->getUnitOfWork()->markReadOnly($user);
    }
}