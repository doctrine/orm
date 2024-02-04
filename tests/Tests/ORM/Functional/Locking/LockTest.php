<?php

declare(strict_types=1);

namespace Doctrine\Tests\ORM\Functional\Locking;

use Doctrine\DBAL\LockMode;
use Doctrine\DBAL\Platforms\SQLitePlatform;
use Doctrine\ORM\OptimisticLockException;
use Doctrine\ORM\Query;
use Doctrine\ORM\TransactionRequiredException;
use Doctrine\Tests\Models\CMS\CmsArticle;
use Doctrine\Tests\Models\CMS\CmsUser;
use Doctrine\Tests\OrmFunctionalTestCase;
use Exception;
use InvalidArgumentException;

/** @group locking */
class LockTest extends OrmFunctionalTestCase
{
    protected function setUp(): void
    {
        $this->useModelSet('cms');

        parent::setUp();
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
     * @group locking
     */
    public function testRefreshWithLockPessimisticWriteNoTransactionThrowsException(): void
    {
        $article        = new CmsArticle();
        $article->text  = 'my article';
        $article->topic = 'Hello';

        $this->_em->persist($article);
        $this->_em->flush();

        $this->expectException(TransactionRequiredException::class);

        $this->_em->refresh($article, LockMode::PESSIMISTIC_WRITE);
    }

    /**
     * @group DDC-178
     * @group locking
     */
    public function testLockPessimisticWrite(): void
    {
        if ($this->_em->getConnection()->getDatabasePlatform() instanceof SQLitePlatform) {
            self::markTestSkipped('Database Driver has no Write Lock support.');
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
        }

        $lastLoggedQuery = $this->getLastLoggedQuery()['sql'];
        // DBAL 2 logs a commit as last query.
        if ($lastLoggedQuery === '"COMMIT"') {
            $lastLoggedQuery = $this->getLastLoggedQuery(1)['sql'];
        }

        self::assertStringContainsString('FOR UPDATE', $lastLoggedQuery);
    }

    /**
     * @group locking
     */
    public function testRefreshWithLockPessimisticWrite(): void
    {
        if ($this->_em->getConnection()->getDatabasePlatform() instanceof SQLitePlatform) {
            self::markTestSkipped('Database Driver has no Write Lock support.');
        }

        $article        = new CmsArticle();
        $article->text  = 'my article';
        $article->topic = 'Hello';

        $this->_em->persist($article);
        $this->_em->flush();

        $this->_em->beginTransaction();
        try {
            $this->_em->refresh($article, LockMode::PESSIMISTIC_WRITE);
            $this->_em->commit();
        } catch (Exception $e) {
            $this->_em->rollback();
        }

        $lastLoggedQuery = $this->getLastLoggedQuery()['sql'];
        // DBAL 2 logs a commit as last query.
        if ($lastLoggedQuery === '"COMMIT"') {
            $lastLoggedQuery = $this->getLastLoggedQuery(1)['sql'];
        }

        self::assertStringContainsString('FOR UPDATE', $lastLoggedQuery);
    }

    /** @group DDC-178 */
    public function testLockPessimisticRead(): void
    {
        if ($this->_em->getConnection()->getDatabasePlatform() instanceof SQLitePlatform) {
            self::markTestSkipped('Database Driver has no Write Lock support.');
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
        }

        $lastLoggedQuery = $this->getLastLoggedQuery()['sql'];
        // DBAL 2 logs a commit as last query.
        if ($lastLoggedQuery === '"COMMIT"') {
            $lastLoggedQuery = $this->getLastLoggedQuery(1)['sql'];
        }

        self::assertThat($lastLoggedQuery, self::logicalOr(
            self::stringContains('FOR UPDATE'),
            self::stringContains('FOR SHARE'),
            self::stringContains('LOCK IN SHARE MODE')
        ));
    }

    /** @group DDC-1693 */
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
