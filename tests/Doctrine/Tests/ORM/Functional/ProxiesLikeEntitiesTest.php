<?php

namespace Doctrine\Tests\ORM\Functional;

use Doctrine\Tests\Models\CMS\CmsAddress;
use Doctrine\Tests\Models\CMS\CmsArticle;
use Doctrine\Tests\Models\CMS\CmsEmail;
use Doctrine\Tests\Models\CMS\CmsGroup;
use Doctrine\Tests\Models\CMS\CmsPhonenumber;
use Doctrine\Tests\Models\CMS\CmsTag;
use Doctrine\Tests\Models\CMS\CmsUser;
use Doctrine\Tests\OrmFunctionalTestCase;
use Doctrine\Tests\Proxies\__CG__\Doctrine\Tests\Models\CMS\CmsUser as CmsUserProxy;

/**
 * Test that Doctrine ORM correctly works with proxy instances exactly like with ordinary Entities
 *
 * The test considers two possible cases:
 *  a) __initialized__ = true and no identifier set in proxy
 *  b) __initialized__ = false and identifier set in proxy and in property
 * @todo All other cases would cause lazy loading
 */
class ProxiesLikeEntitiesTest extends OrmFunctionalTestCase
{
    /**
     * @var CmsUser
     */
    protected $user;

    protected function setUp()
    {
        parent::setUp();
        try {
            $this->_schemaTool->createSchema(
                [
                    $this->_em->getClassMetadata(CmsUser::class),
                    $this->_em->getClassMetadata(CmsTag::class),
                    $this->_em->getClassMetadata(CmsPhonenumber::class),
                    $this->_em->getClassMetadata(CmsArticle::class),
                    $this->_em->getClassMetadata(CmsAddress::class),
                    $this->_em->getClassMetadata(CmsEmail::class),
                    $this->_em->getClassMetadata(CmsGroup::class),
                ]
            );
        } catch (\Exception $e) {
        }
        $this->user = new CmsUser();
        $this->user->username = 'ocramius';
        $this->user->name = 'Marco';
        $this->_em->persist($this->user);
        $this->_em->flush();
        $this->_em->clear();
    }

    /**
     * Verifies that a proxy can be successfully persisted and updated
     */
    public function testPersistUpdate()
    {
        // Considering case (a)
        $proxy = $this->_em->getProxyFactory()->getProxy(CmsUser::class, ['id' => 123]);
        $proxy->__isInitialized__ = true;
        $proxy->id = null;
        $proxy->username = 'ocra';
        $proxy->name = 'Marco';
        $this->_em->persist($proxy);
        $this->_em->flush();
        $this->assertNotNull($proxy->getId());
        $proxy->name = 'Marco Pivetta';
        $this->_em->getUnitOfWork()
            ->computeChangeSet($this->_em->getClassMetadata(CmsUser::class), $proxy);
        $this->assertNotEmpty($this->_em->getUnitOfWork()->getEntityChangeSet($proxy));
        $this->assertEquals('Marco Pivetta', $this->_em->find(CmsUser::class, $proxy->getId())->name);
        $this->_em->remove($proxy);
        $this->_em->flush();
    }

    public function testEntityWithIdentifier()
    {
        $userId = $this->user->getId();
        /* @var $uninitializedProxy CmsUserProxy */
        $uninitializedProxy = $this->_em->getReference(CmsUser::class, $userId);
        $this->assertInstanceOf(CmsUserProxy::class, $uninitializedProxy);

        $this->_em->persist($uninitializedProxy);
        $this->_em->flush();
        $this->assertFalse($uninitializedProxy->__isInitialized(), 'Proxy didn\'t get initialized during flush operations');
        $this->assertEquals($userId, $uninitializedProxy->getId());
        $this->_em->remove($uninitializedProxy);
        $this->_em->flush();
    }

    /**
     * Verifying that proxies can be used without problems as query parameters
     */
    public function testProxyAsDqlParameterPersist()
    {
        $proxy = $this->_em->getProxyFactory()->getProxy(CmsUser::class, ['id' => $this->user->getId()]
        );
        $proxy->id = $this->user->getId();
        $result = $this
            ->_em
            ->createQuery('SELECT u FROM Doctrine\Tests\Models\CMS\CmsUser u WHERE u = ?1')
            ->setParameter(1, $proxy)
            ->getSingleResult();
        $this->assertSame($this->user->getId(), $result->getId());
        $this->_em->remove($proxy);
        $this->_em->flush();
    }

    /**
     * Verifying that proxies can be used without problems as query parameters
     */
    public function testFindWithProxyName()
    {
        $result = $this->_em->find(CmsUserProxy::class, $this->user->getId());
        $this->assertSame($this->user->getId(), $result->getId());
        $this->_em->clear();

        $result = $this->_em->getReference(CmsUserProxy::class, $this->user->getId());
        $this->assertSame($this->user->getId(), $result->getId());
        $this->_em->clear();

        $result = $this->_em->getRepository(CmsUserProxy::class)->findOneBy(['username' => $this->user->username]);
        $this->assertSame($this->user->getId(), $result->getId());
        $this->_em->clear();

        $result = $this->_em
            ->createQuery('SELECT u FROM Doctrine\Tests\Proxies\__CG__\Doctrine\Tests\Models\CMS\CmsUser u WHERE u.id = ?1')
            ->setParameter(1, $this->user->getId())
            ->getSingleResult();

        $this->assertSame($this->user->getId(), $result->getId());
        $this->_em->clear();
    }

    protected function tearDown()
    {
        $this->_em->createQuery('DELETE FROM Doctrine\Tests\Models\CMS\CmsUser u')->execute();
    }
}
