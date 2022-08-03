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
    protected function setUp(): void
    {
        $this->useModelSet('cms');
        parent::setUp();

        $this->handles = [];
    }

    /**
     * @group DDC-178
     * @group locking
     * @testWith [false]
     *           [true]
     */
    public function testLockVersionedEntity(bool $useStringVersion): void
    {
        $article        = new CmsArticle();
        $article->text  = 'my article';
        $article->topic = 'Hello';

        $this->_em->persist($article);
        $this->_em->flush();

        $lockVersion = $article->version;
        if ($useStringVersion) {
            // NOTE: Officially, the lock method (and callers) do not accept a string argument. Calling code should
            // cast the version to (int) as per the docs. However, this is not currently enforced. This may change in
            // a future release.
            $lockVersion = (string) $lockVersion;
        }

        $this->_em->lock($article, LockMode::OPTIMISTIC, $lockVersion);

        $this->addToAssertionCount(1);
    }

    /**
     * @group DDC-178
     * @group locking
     * @testWith [false]
     *           [true]
     */
    public function testLockVersionedEntityMismatchThrowsException(bool $useStringVersion): void
    {
        $article        = new CmsArticle();
        $article->text  = 'my article';
        $article->topic = 'Hello';

        $this->_em->persist($article);
        $this->_em->flush();

        $this->expectException(OptimisticLockException::class);
        $lockVersion = $article->version + 1;
        if ($useStringVersion) {
            $lockVersion = (string) $lockVersion;
        }

        $this->_em->lock($article, LockMode::OPTIMISTIC, $lockVersion);
    }

    /**
     * @group DDC-178
     * @group locking
     */
    public function testLockUnversionedEntityThrowsException(): void
    {
        $user           = new CmsUser();
        $user->name     = 'foo';
        $user->status   = 'active';
        $user->username = 'foo';

        $this->_em->persist($user);
        $this->_em->flush();

        $this->expectException(OptimisticLockException::class);

        $this->_em->lock($user, LockMode::OPTIMISTIC);
    }

    /**
     * @group DDC-178
     * @group locking
     */
    public function testLockUnmanagedEntityThrowsException(): void
    {
        $article = new CmsArticle();

        $this->expectException(InvalidArgumentException::class);
        $this->expectExceptionMessage('Entity ' . CmsArticle::class);

        $this->_em->lock($article, LockMode::OPTIMISTIC, $article->version + 1);
    }

    /**
     * @group DDC-178
     * @group locking
     */
    public function testLockPessimisticReadNoTransactionThrowsException(): void
    {
        $article        = new CmsArticle();
        $article->text  = 'my article';
        $article->topic = 'Hello';

        $this->_em->persist($article);
        $this->_em->flush();

        $this->expectException(TransactionRequiredException::class);

        $this->_em->lock($article, LockMode::PESSIMISTIC_READ);
    }

    /**
     * @group DDC-178
     * @group locking
     */
    public function testLockPessimisticWriteNoTransactionThrowsException(): void
    {
        $article        = new CmsArticle();
        $article->text  = 'my article';
        $article->topic = 'Hello';

        $this->_em->persist($article);
        $this->_em->flush();

        $this->expectException(TransactionRequiredException::class);

        $this->_em->lock($article, LockMode::PESSIMISTIC_WRITE);
    }

    /**
     * @group DDC-178
     * @group locking
     */
    public function testLockPessimisticWrite(): void
    {
        $writeLockSql = $this->_em->getConnection()->getDatabasePlatform()->getWriteLockSQL();

        if (! $writeLockSql) {
            $this->markTestSkipped('Database Driver has no Write Lock support.');
        }

        $article        = new CmsArticle();
        $article->text  = 'my article';
        $article->topic = 'Hello';

        $this->_em->persist($article);
        $this->_em->flush();

        $this->_em->beginTransaction();
        try {
            $this->_em->lock($article, LockMode::PESSIMISTIC_WRITE);
            $this->_em->commit();
        } catch (Exception $e) {
            $this->_em->rollback();

            throw $e;
        }

        $query = array_pop($this->_sqlLoggerStack->queries);
        $query = array_pop($this->_sqlLoggerStack->queries);
        $this->assertStringContainsString($writeLockSql, $query['sql']);
    }

    /**
     * @group DDC-178
     */
    public function testLockPessimisticRead(): void
    {
        $readLockSql = $this->_em->getConnection()->getDatabasePlatform()->getReadLockSQL();

        if (! $readLockSql) {
            $this->markTestSkipped('Database Driver has no Write Lock support.');
        }

        $article        = new CmsArticle();
        $article->text  = 'my article';
        $article->topic = 'Hello';

        $this->_em->persist($article);
        $this->_em->flush();

        $this->_em->beginTransaction();

        try {
            $this->_em->lock($article, LockMode::PESSIMISTIC_READ);
            $this->_em->commit();
        } catch (Exception $e) {
            $this->_em->rollback();

            throw $e;
        }

        array_pop($this->_sqlLoggerStack->queries);
        $query = array_pop($this->_sqlLoggerStack->queries);

        $this->assertStringContainsString($readLockSql, $query['sql']);
    }

    /**
     * @group DDC-1693
     */
    public function testLockOptimisticNonVersionedThrowsExceptionInDQL(): void
    {
        $dql = 'SELECT u FROM ' . CmsUser::class . " u WHERE u.username = 'gblanco'";

        $this->expectException(OptimisticLockException::class);
        $this->expectExceptionMessage('The optimistic lock on an entity failed.');

        $this->_em->createQuery($dql)
                  ->setHint(Query::HINT_LOCK_MODE, LockMode::OPTIMISTIC)
                  ->getSQL();
    }
}
