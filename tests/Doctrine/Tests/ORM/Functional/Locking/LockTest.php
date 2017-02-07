<?php

namespace Doctrine\Tests\ORM\Functional\Locking;

use Doctrine\DBAL\LockMode;
use Doctrine\ORM\OptimisticLockException;
use Doctrine\ORM\Query;
use Doctrine\ORM\TransactionRequiredException;
use Doctrine\Tests\Models\CMS\CmsArticle;
use Doctrine\Tests\Models\CMS\CmsUser;
use Doctrine\Tests\OrmFunctionalTestCase;

/**
 * @group locking
 */
class LockTest extends OrmFunctionalTestCase
{
    protected function setUp()
    {
        $this->useModelSet('cms');
        parent::setUp();

        $this->handles = [];
    }

    /**
     * @group DDC-178
     * @group locking
     */
    public function testLockVersionedEntity()
    {
        $article = new CmsArticle();
        $article->text = "my article";
        $article->topic = "Hello";

        $this->em->persist($article);
        $this->em->flush();

        $this->em->lock($article, LockMode::OPTIMISTIC, $article->version);

        self::addToAssertionCount(1);
    }

    /**
     * @group DDC-178
     * @group locking
     */
    public function testLockVersionedEntity_MismatchThrowsException()
    {
        $article = new CmsArticle();
        $article->text = "my article";
        $article->topic = "Hello";

        $this->em->persist($article);
        $this->em->flush();

        $this->expectException(OptimisticLockException::class);

        $this->em->lock($article, LockMode::OPTIMISTIC, $article->version + 1);
    }

    /**
     * @group DDC-178
     * @group locking
     */
    public function testLockUnversionedEntity_ThrowsException()
    {
        $user = new CmsUser();
        $user->name = "foo";
        $user->status = "active";
        $user->username = "foo";

        $this->em->persist($user);
        $this->em->flush();

        $this->expectException(OptimisticLockException::class);

        $this->em->lock($user, LockMode::OPTIMISTIC);
    }

    /**
     * @group DDC-178
     * @group locking
     */
    public function testLockUnmanagedEntity_ThrowsException()
    {
        $article = new CmsArticle();

        $this->expectException(\InvalidArgumentException::class);
        $this->expectExceptionMessage('Entity ' . CmsArticle::class);

        $this->em->lock($article, LockMode::OPTIMISTIC, $article->version + 1);
    }

    /**
     * @group DDC-178
     * @group locking
     */
    public function testLockPessimisticRead_NoTransaction_ThrowsException()
    {
        $article = new CmsArticle();
        $article->text = "my article";
        $article->topic = "Hello";

        $this->em->persist($article);
        $this->em->flush();

        $this->expectException(TransactionRequiredException::class);

        $this->em->lock($article, LockMode::PESSIMISTIC_READ);
    }

    /**
     * @group DDC-178
     * @group locking
     */
    public function testLockPessimisticWrite_NoTransaction_ThrowsException()
    {
        $article = new CmsArticle();
        $article->text = "my article";
        $article->topic = "Hello";

        $this->em->persist($article);
        $this->em->flush();

        $this->expectException(TransactionRequiredException::class);

        $this->em->lock($article, LockMode::PESSIMISTIC_WRITE);
    }

    /**
     * @group DDC-178
     * @group locking
     */
    public function testLockPessimisticWrite()
    {
        $writeLockSql = $this->em->getConnection()->getDatabasePlatform()->getWriteLockSQL();

        if (! $writeLockSql) {
            $this->markTestSkipped('Database Driver has no Write Lock support.');
        }

        $article = new CmsArticle();
        $article->text = "my article";
        $article->topic = "Hello";

        $this->em->persist($article);
        $this->em->flush();

        $this->em->beginTransaction();

        try {
            $this->em->lock($article, LockMode::PESSIMISTIC_WRITE);
            $this->em->commit();
        } catch (\Exception $e) {
            $this->em->rollback();
            throw $e;
        }

        array_pop($this->sqlLoggerStack->queries);

        $query = array_pop($this->sqlLoggerStack->queries);

        self::assertContains($writeLockSql, $query['sql']);
    }

    /**
     * @group DDC-178
     */
    public function testLockPessimisticRead()
    {
        $readLockSql = $this->em->getConnection()->getDatabasePlatform()->getReadLockSQL();

        if (! $readLockSql) {
            $this->markTestSkipped('Database Driver has no Write Lock support.');
        }

        $article = new CmsArticle();
        $article->text = "my article";
        $article->topic = "Hello";

        $this->em->persist($article);
        $this->em->flush();

        $this->em->beginTransaction();

        try {
            $this->em->lock($article, LockMode::PESSIMISTIC_READ);
            $this->em->commit();
        } catch (\Exception $e) {
            $this->em->rollback();
            throw $e;
        }

        array_pop($this->sqlLoggerStack->queries);

        $query = array_pop($this->sqlLoggerStack->queries);

        self::assertContains($readLockSql, $query['sql']);
    }

    /**
     * @group DDC-1693
     */
    public function testLockOptimisticNonVersionedThrowsExceptionInDQL()
    {
        $dql = "SELECT u FROM " . CmsUser::class . " u WHERE u.username = 'gblanco'";

        $this->expectException(OptimisticLockException::class);
        $this->expectExceptionMessage('The optimistic lock on an entity failed.');

        $this->em->createQuery($dql)
                  ->setHint(Query::HINT_LOCK_MODE, LockMode::OPTIMISTIC)
                  ->getSQL();
    }
}
