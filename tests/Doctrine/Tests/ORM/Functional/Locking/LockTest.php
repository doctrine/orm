<?php

declare(strict_types=1);

namespace Doctrine\Tests\ORM\Functional\Locking;

use Doctrine\DBAL\LockMode;
use Doctrine\ORM\OptimisticLockException;
use Doctrine\ORM\Query;
use Doctrine\ORM\TransactionRequiredException;
use Doctrine\Tests\Models\CMS\CmsArticle;
use Doctrine\Tests\Models\CMS\CmsUser;
use Doctrine\Tests\OrmFunctionalTestCase;
use Exception;
use InvalidArgumentException;
use function array_pop;

/**
 * @group locking
 */
class LockTest extends OrmFunctionalTestCase
{
    protected function setUp() : void
    {
        $this->useModelSet('cms');
        parent::setUp();

        $this->handles = [];
    }

    /**
     * @group DDC-178
     * @group locking
     */
    public function testLockVersionedEntity() : void
    {
        $article        = new CmsArticle();
        $article->text  = 'my article';
        $article->topic = 'Hello';

        $this->em->persist($article);
        $this->em->flush();

        $this->em->lock($article, LockMode::OPTIMISTIC, $article->version);

        self::addToAssertionCount(1);
    }

    /**
     * @group DDC-178
     * @group locking
     */
    public function testLockVersionedEntityMismatchThrowsException() : void
    {
        $article        = new CmsArticle();
        $article->text  = 'my article';
        $article->topic = 'Hello';

        $this->em->persist($article);
        $this->em->flush();

        $this->expectException(OptimisticLockException::class);

        $this->em->lock($article, LockMode::OPTIMISTIC, $article->version + 1);
    }

    /**
     * @group DDC-178
     * @group locking
     */
    public function testLockUnversionedEntityThrowsException() : void
    {
        $user           = new CmsUser();
        $user->name     = 'foo';
        $user->status   = 'active';
        $user->username = 'foo';

        $this->em->persist($user);
        $this->em->flush();

        $this->expectException(OptimisticLockException::class);

        $this->em->lock($user, LockMode::OPTIMISTIC);
    }

    /**
     * @group DDC-178
     * @group locking
     */
    public function testLockUnmanagedEntityThrowsException() : void
    {
        $article = new CmsArticle();

        $this->expectException(InvalidArgumentException::class);
        $this->expectExceptionMessage('Entity ' . CmsArticle::class);

        $this->em->lock($article, LockMode::OPTIMISTIC, $article->version + 1);
    }

    /**
     * @group DDC-178
     * @group locking
     */
    public function testLockPessimisticReadNoTransactionThrowsException() : void
    {
        $article        = new CmsArticle();
        $article->text  = 'my article';
        $article->topic = 'Hello';

        $this->em->persist($article);
        $this->em->flush();

        $this->expectException(TransactionRequiredException::class);

        $this->em->lock($article, LockMode::PESSIMISTIC_READ);
    }

    /**
     * @group DDC-178
     * @group locking
     */
    public function testLockPessimisticWriteNoTransactionThrowsException() : void
    {
        $article        = new CmsArticle();
        $article->text  = 'my article';
        $article->topic = 'Hello';

        $this->em->persist($article);
        $this->em->flush();

        $this->expectException(TransactionRequiredException::class);

        $this->em->lock($article, LockMode::PESSIMISTIC_WRITE);
    }

    /**
     * @group DDC-178
     * @group locking
     */
    public function testLockPessimisticWrite() : void
    {
        $writeLockSql = $this->em->getConnection()->getDatabasePlatform()->getWriteLockSQL();

        if (! $writeLockSql) {
            $this->markTestSkipped('Database Driver has no Write Lock support.');
        }

        $article        = new CmsArticle();
        $article->text  = 'my article';
        $article->topic = 'Hello';

        $this->em->persist($article);
        $this->em->flush();

        $this->em->beginTransaction();

        try {
            $this->em->lock($article, LockMode::PESSIMISTIC_WRITE);
            $this->em->commit();
        } catch (Exception $e) {
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
    public function testLockPessimisticRead() : void
    {
        $readLockSql = $this->em->getConnection()->getDatabasePlatform()->getReadLockSQL();

        if (! $readLockSql) {
            $this->markTestSkipped('Database Driver has no Write Lock support.');
        }

        $article        = new CmsArticle();
        $article->text  = 'my article';
        $article->topic = 'Hello';

        $this->em->persist($article);
        $this->em->flush();

        $this->em->beginTransaction();

        try {
            $this->em->lock($article, LockMode::PESSIMISTIC_READ);
            $this->em->commit();
        } catch (Exception $e) {
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
    public function testLockOptimisticNonVersionedThrowsExceptionInDQL() : void
    {
        $dql = 'SELECT u FROM ' . CmsUser::class . " u WHERE u.username = 'gblanco'";

        $this->expectException(OptimisticLockException::class);
        $this->expectExceptionMessage('The optimistic lock on an entity failed.');

        $this->em->createQuery($dql)
                  ->setHint(Query::HINT_LOCK_MODE, LockMode::OPTIMISTIC)
                  ->getSQL();
    }
}
