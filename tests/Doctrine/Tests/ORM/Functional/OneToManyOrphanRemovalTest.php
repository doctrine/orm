<?php

namespace Doctrine\Tests\ORM\Functional;

use Doctrine\Tests\Models\CMS\CmsPhonenumber;
use Doctrine\Tests\Models\CMS\CmsUser;
use Doctrine\Tests\OrmFunctionalTestCase;

/**
 * Tests a bidirectional one-to-many association mapping with orphan removal.
 */
class OneToManyOrphanRemovalTest extends OrmFunctionalTestCase
{
    protected $userId;

    protected function setUp()
    {
        $this->useModelSet('cms');

        parent::setUp();

        $user = new CmsUser;
        $user->status = 'dev';
        $user->username = 'romanb';
        $user->name = 'Roman B.';

        $phone1 = new CmsPhonenumber;
        $phone1->phonenumber = '123456';

        $phone2 = new CmsPhonenumber;
        $phone2->phonenumber = '234567';

        $user->addPhonenumber($phone1);
        $user->addPhonenumber($phone2);

        $this->em->persist($user);
        $this->em->flush();

        $this->userId = $user->getId();
        $this->em->clear();
    }

    public function testOrphanRemoval()
    {
        $userProxy = $this->em->getReference(CmsUser::class, $this->userId);

        $this->em->remove($userProxy);
        $this->em->flush();
        $this->em->clear();

        $query  = $this->em->createQuery('SELECT u FROM Doctrine\Tests\Models\CMS\CmsUser u');
        $result = $query->getResult();

        self::assertEquals(0, count($result), 'CmsUser should be removed by EntityManager');

        $query  = $this->em->createQuery('SELECT p FROM Doctrine\Tests\Models\CMS\CmsPhonenumber p');
        $result = $query->getResult();

        self::assertEquals(0, count($result), 'CmsPhonenumber should be removed by orphanRemoval');
    }

    /**
     * @group DDC-3382
     */
    public function testOrphanRemovalRemoveFromCollection()
    {
        $user = $this->em->find(CmsUser::class, $this->userId);

        $phonenumber = $user->getPhonenumbers()->remove(0);

        $this->em->flush();
        $this->em->clear();

        $query  = $this->em->createQuery('SELECT p FROM Doctrine\Tests\Models\CMS\CmsPhonenumber p');
        $result = $query->getResult();

        self::assertEquals(1, count($result), 'CmsPhonenumber should be removed by orphanRemoval');
    }

    /**
     * @group DDC-3382
     */
    public function testOrphanRemovalClearCollectionAndReAdd()
    {
        $user = $this->em->find(CmsUser::class, $this->userId);

        $phone1 = $user->getPhonenumbers()->first();

        $user->getPhonenumbers()->clear();
        $user->addPhonenumber($phone1);

        $this->em->flush();

        $query  = $this->em->createQuery('SELECT p FROM Doctrine\Tests\Models\CMS\CmsPhonenumber p');
        $result = $query->getResult();

        self::assertEquals(1, count($result), 'CmsPhonenumber should be removed by orphanRemoval');
    }

    /**
     * @group DDC-2524
     */
    public function testOrphanRemovalClearCollectionAndAddNew()
    {
        $user     = $this->em->find(CmsUser::class, $this->userId);
        $newPhone = new CmsPhonenumber();

        $newPhone->phonenumber = '654321';

        $user->getPhonenumbers()->clear();
        $user->addPhonenumber($newPhone);

        $this->em->flush();

        $query  = $this->em->createQuery('SELECT p FROM Doctrine\Tests\Models\CMS\CmsPhonenumber p');
        $result = $query->getResult();

        self::assertEquals(1, count($result), 'Old CmsPhonenumbers should be removed by orphanRemoval and new one added');
    }

    /**
     * @group DDC-1496
     */
    public function testOrphanRemovalUnitializedCollection()
    {
        $user = $this->em->find(CmsUser::class, $this->userId);

        $user->phonenumbers->clear();
        $this->em->flush();

        $query  = $this->em->createQuery('SELECT p FROM Doctrine\Tests\Models\CMS\CmsPhonenumber p');
        $result = $query->getResult();

        self::assertEquals(0, count($result), 'CmsPhonenumber should be removed by orphanRemoval');
    }
}
