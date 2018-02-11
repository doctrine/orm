<?php

declare(strict_types=1);

namespace Doctrine\Tests\ORM\Functional;

use Doctrine\Tests\Models\CMS\CmsAddress;
use Doctrine\Tests\Models\CMS\CmsEmail;
use Doctrine\Tests\Models\CMS\CmsUser;
use Doctrine\Tests\OrmFunctionalTestCase;

/**
 * Tests a bidirectional one-to-one association mapping with orphan removal.
 */
class OneToOneOrphanRemovalTest extends OrmFunctionalTestCase
{
    protected function setUp()
    {
        $this->useModelSet('cms');

        parent::setUp();
    }

    public function testOrphanRemoval()
    {
        $user           = new CmsUser();
        $user->status   = 'dev';
        $user->username = 'romanb';
        $user->name     = 'Roman B.';

        $address          = new CmsAddress();
        $address->country = 'de';
        $address->zip     = 1234;
        $address->city    = 'Berlin';

        $user->setAddress($address);

        $this->em->persist($user);
        $this->em->flush();

        $userId = $user->getId();

        $this->em->clear();

        $userProxy = $this->em->getReference(CmsUser::class, $userId);

        $this->em->remove($userProxy);
        $this->em->flush();
        $this->em->clear();

        $query  = $this->em->createQuery('SELECT u FROM Doctrine\Tests\Models\CMS\CmsUser u');
        $result = $query->getResult();

        self::assertCount(0, $result, 'CmsUser should be removed by EntityManager');

        $query  = $this->em->createQuery('SELECT a FROM Doctrine\Tests\Models\CMS\CmsAddress a');
        $result = $query->getResult();

        self::assertCount(0, $result, 'CmsAddress should be removed by orphanRemoval');
    }

    public function testOrphanRemovalWhenUnlink()
    {
        $user           = new CmsUser();
        $user->status   = 'dev';
        $user->username = 'beberlei';
        $user->name     = 'Benjamin Eberlei';

        $email        = new CmsEmail();
        $email->email = 'beberlei@domain.com';

        $user->setEmail($email);

        $this->em->persist($user);
        $this->em->flush();

        $userId = $user->getId();

        $this->em->clear();

        $user = $this->em->find(CmsUser::class, $userId);

        $user->setEmail(null);

        $this->em->persist($user);
        $this->em->flush();
        $this->em->clear();

        $query  = $this->em->createQuery('SELECT e FROM Doctrine\Tests\Models\CMS\CmsEmail e');
        $result = $query->getResult();

        self::assertCount(0, $result, 'CmsEmail should be removed by orphanRemoval');
    }
}
