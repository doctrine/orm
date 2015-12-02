<?php

declare(strict_types=1);

namespace Doctrine\Tests\ORM\Functional;

use Doctrine\DBAL\Exception\UniqueConstraintViolationException;
use Doctrine\ORM\OptimisticLockException;
use Doctrine\ORM\ORMException;
use Doctrine\ORM\Proxy\Proxy;
use Doctrine\Tests\Models\CMS\CmsUser;
use Doctrine\Tests\Models\CMS\CmsPhonenumber;
use Doctrine\Tests\Models\CMS\CmsAddress;
use Doctrine\Tests\Models\CMS\CmsArticle;
use Doctrine\Tests\OrmFunctionalTestCase;

/**
 * Description of DetachedEntityTest
 *
 * @author robo
 */
class DetachedEntityTest extends OrmFunctionalTestCase
{
    protected function setUp()
    {
        $this->useModelSet('cms');
        parent::setUp();
    }

    /**
     * @group DDC-203
     */
    public function testDetachedEntityThrowsExceptionOnFlush()
    {
        $this->expectException(UniqueConstraintViolationException::class);

        $ph = new CmsPhonenumber();
        $ph->phonenumber = '12345';

        $this->em->persist($ph);
        $this->em->flush();
        $this->em->clear();

        $this->em->persist($ph);
        $this->em->flush();
    }

    /**
     * @group DDC-822
     */
    public function testUseDetachedEntityAsQueryParameter()
    {
        $user = new CmsUser;
        $user->name = 'Guilherme';
        $user->username = 'gblanco';
        $user->status = 'developer';

        $this->em->persist($user);

        $this->em->flush();
        $this->em->detach($user);

        $dql = 'SELECT u FROM ' . CmsUser::class . ' u WHERE u.id = ?1';
        $query = $this->em->createQuery($dql);

        $query->setParameter(1, $user);

        $newUser = $query->getSingleResult();

        self::assertInstanceOf(CmsUser::class, $newUser);
        self::assertEquals('gblanco', $newUser->username);
    }

    /**
     * @group DDC-920
     */
    public function testDetachManagedUnpersistedEntity()
    {
        $user = new CmsUser;
        $user->name = 'Guilherme';
        $user->username = 'gblanco';
        $user->status = 'developer';

        $this->em->persist($user);
        $this->em->detach($user);

        $this->em->flush();

        self::assertFalse($this->em->contains($user));
        self::assertFalse($this->em->getUnitOfWork()->isInIdentityMap($user));
    }
}

