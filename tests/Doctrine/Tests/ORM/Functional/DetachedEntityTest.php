<?php

declare(strict_types=1);

namespace Doctrine\Tests\ORM\Functional;

use Doctrine\DBAL\Exception\UniqueConstraintViolationException;
use Doctrine\ORM\OptimisticLockException;
use Doctrine\Persistence\Proxy;
use Doctrine\Tests\Models\CMS\CmsAddress;
use Doctrine\Tests\Models\CMS\CmsArticle;
use Doctrine\Tests\Models\CMS\CmsPhonenumber;
use Doctrine\Tests\Models\CMS\CmsUser;
use Doctrine\Tests\OrmFunctionalTestCase;

/**
 * Description of DetachedEntityTest
 */
class DetachedEntityTest extends OrmFunctionalTestCase
{
    protected function setUp(): void
    {
        $this->useModelSet('cms');
        parent::setUp();
    }

    /** @group DDC-203 */
    public function testDetachedEntityThrowsExceptionOnFlush(): void
    {
        $ph              = new CmsPhonenumber();
        $ph->phonenumber = '12345';

        $this->_em->persist($ph);
        $this->_em->flush();
        $this->_em->clear();

        $this->_em->persist($ph);

        // since it tries to insert the object twice (with the same PK)
        $this->expectException(UniqueConstraintViolationException::class);
        $this->_em->flush();
    }

    /** @group DDC-822 */
    public function testUseDetachedEntityAsQueryParameter(): void
    {
        $user           = new CmsUser();
        $user->name     = 'Guilherme';
        $user->username = 'gblanco';
        $user->status   = 'developer';

        $this->_em->persist($user);

        $this->_em->flush();
        $this->_em->detach($user);

        $dql   = 'SELECT u FROM ' . CmsUser::class . ' u WHERE u.id = ?1';
        $query = $this->_em->createQuery($dql);
        $query->setParameter(1, $user);

        $newUser = $query->getSingleResult();

        self::assertInstanceOf(CmsUser::class, $newUser);
        self::assertEquals('gblanco', $newUser->username);
    }

    /** @group DDC-920 */
    public function testDetachManagedUnpersistedEntity(): void
    {
        $user           = new CmsUser();
        $user->name     = 'Guilherme';
        $user->username = 'gblanco';
        $user->status   = 'developer';

        $this->_em->persist($user);
        $this->_em->detach($user);

        $this->_em->flush();

        self::assertFalse($this->_em->contains($user));
        self::assertFalse($this->_em->getUnitOfWork()->isInIdentityMap($user));
    }
}
