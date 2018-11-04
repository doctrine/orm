<?php

declare(strict_types=1);

namespace Doctrine\Tests\ORM\Functional;

use Doctrine\Tests\Models\CMS\CmsAddress;
use Doctrine\Tests\Models\CMS\CmsArticle;
use Doctrine\Tests\Models\CMS\CmsEmail;
use Doctrine\Tests\Models\CMS\CmsGroup;
use Doctrine\Tests\Models\CMS\CmsPhonenumber;
use Doctrine\Tests\Models\CMS\CmsTag;
use Doctrine\Tests\Models\CMS\CmsUser;
use Doctrine\Tests\OrmFunctionalTestCase;
use Exception;
use ProxyManager\Configuration;
use ProxyManager\Proxy\GhostObjectInterface;
use function sprintf;

/**
 * Test that Doctrine ORM correctly works with proxy instances exactly like with ordinary Entities
 *
 * The test considers two possible cases:
 *  a) __initialized__ = true and no identifier set in proxy
 *  b) __initialized__ = false and identifier set in proxy and in property
 *
 * @todo All other cases would cause lazy loading
 */
class ProxiesLikeEntitiesTest extends OrmFunctionalTestCase
{
    /** @var CmsUser */
    protected $user;

    /** @var string */
    private $proxyClassName;

    protected function setUp() : void
    {
        parent::setUp();
        try {
            $this->schemaTool->createSchema(
                [
                    $this->em->getClassMetadata(CmsUser::class),
                    $this->em->getClassMetadata(CmsTag::class),
                    $this->em->getClassMetadata(CmsPhonenumber::class),
                    $this->em->getClassMetadata(CmsArticle::class),
                    $this->em->getClassMetadata(CmsAddress::class),
                    $this->em->getClassMetadata(CmsEmail::class),
                    $this->em->getClassMetadata(CmsGroup::class),
                ]
            );
        } catch (Exception $e) {
        }
        $this->user           = new CmsUser();
        $this->user->username = 'ocramius';
        $this->user->name     = 'Marco';
        $this->em->persist($this->user);
        $this->em->flush();
        $this->em->clear();

        $this->proxyClassName = (new Configuration())->getClassNameInflector()->getProxyClassName(CmsUser::class);
    }

    /**
     * Verifies that a proxy can be successfully persisted and updated
     */
    public function testPersistUpdate() : void
    {
        // Considering case (a)
        $metadata = $this->em->getClassMetadata(CmsUser::class);
        $proxy    = $this->em->getProxyFactory()->getProxy($metadata, ['id' => 123]);

        $proxy->setProxyInitializer(null);
        $proxy->id       = null;
        $proxy->username = 'ocra';
        $proxy->name     = 'Marco';

        $this->em->persist($proxy);
        $this->em->flush();

        self::assertNotNull($proxy->getId());

        $proxy->name = 'Marco Pivetta';

        $this->em->getUnitOfWork()->computeChangeSet($metadata, $proxy);
        self::assertNotEmpty($this->em->getUnitOfWork()->getEntityChangeSet($proxy));
        self::assertEquals('Marco Pivetta', $this->em->find(CmsUser::class, $proxy->getId())->name);

        $this->em->remove($proxy);
        $this->em->flush();
    }

    public function testEntityWithIdentifier() : void
    {
        $userId = $this->user->getId();
        /** @var CmsUser|GhostObjectInterface $uninitializedProxy */
        $uninitializedProxy = $this->em->getReference(CmsUser::class, $userId);
        self::assertInstanceOf(GhostObjectInterface::class, $uninitializedProxy);
        self::assertInstanceOf(CmsUser::class, $uninitializedProxy);
        self::assertFalse($uninitializedProxy->isProxyInitialized());

        $this->em->persist($uninitializedProxy);
        $this->em->flush();
        self::assertFalse($uninitializedProxy->isProxyInitialized(), 'Proxy didn\'t get initialized during flush operations');
        self::assertEquals($userId, $uninitializedProxy->getId());
        $this->em->remove($uninitializedProxy);
        $this->em->flush();
    }

    /**
     * Verifying that proxies can be used without problems as query parameters
     */
    public function testProxyAsDqlParameterPersist() : void
    {
        $proxy = $this->em->getProxyFactory()->getProxy(
            $this->em->getClassMetadata(CmsUser::class),
            ['id' => $this->user->getId()]
        );

        $proxy->id = $this->user->getId();

        $result = $this
            ->em
            ->createQuery('SELECT u FROM Doctrine\Tests\Models\CMS\CmsUser u WHERE u = ?1')
            ->setParameter(1, $proxy)
            ->getSingleResult();

        self::assertSame($this->user->getId(), $result->getId());

        $this->em->remove($proxy);
        $this->em->flush();
    }

    /**
     * Verifying that proxies can be used without problems as query parameters
     */
    public function testFindWithProxyName() : void
    {
        self::assertNotEquals(CmsUser::class, $this->proxyClassName);

        $result = $this->em->find($this->proxyClassName, $this->user->getId());

        self::assertSame($this->user->getId(), $result->getId());

        $this->em->clear();

        $result = $this->em->getReference($this->proxyClassName, $this->user->getId());

        self::assertSame($this->user->getId(), $result->getId());

        $this->em->clear();

        $result = $this->em->getRepository($this->proxyClassName)->findOneBy([
            'username' => $this->user->username,
        ]);

        self::assertSame($this->user->getId(), $result->getId());

        $this->em->clear();

        $result = $this->em
            ->createQuery(sprintf('SELECT u FROM %s u WHERE u.id = ?1', $this->proxyClassName))
            ->setParameter(1, $this->user->getId())
            ->getSingleResult();

        self::assertSame($this->user->getId(), $result->getId());

        $this->em->clear();
    }

    protected function tearDown() : void
    {
        $this->em->createQuery('DELETE FROM Doctrine\Tests\Models\CMS\CmsUser u')->execute();
    }
}
