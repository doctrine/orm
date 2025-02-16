<?php

declare(strict_types=1);

namespace Doctrine\Tests\ORM\Functional\Ticket;

use Doctrine\Tests\Models\CMS\CmsUser;
use Doctrine\Tests\OrmFunctionalTestCase;
use Symfony\Component\Cache\Adapter\ArrayAdapter;

class GH11112Test extends OrmFunctionalTestCase
{
    protected function setUp(): void
    {
        $this->useModelSet('cms');
        self::$queryCache = new ArrayAdapter();

        parent::setUp();
    }

    public function testSimpleQueryHasLimitAndOffsetApplied(): void
    {
        $platform    = $this->_em->getConnection()->getDatabasePlatform();
        $query       = $this->_em->createQuery('SELECT u FROM ' . CmsUser::class . ' u');
        $originalSql = $query->getSQL();

        $query->setMaxResults(10);
        $query->setFirstResult(20);
        $sqlMax10First20 = $query->getSQL();

        $query->setMaxResults(30);
        $query->setFirstResult(40);
        $sqlMax30First40 = $query->getSQL();

        // The SQL is platform specific and may even be something with outer SELECTS being added. So,
        // derive the expected value at runtime through the platform.
        self::assertSame($platform->modifyLimitQuery($originalSql, 10, 20), $sqlMax10First20);
        self::assertSame($platform->modifyLimitQuery($originalSql, 30, 40), $sqlMax30First40);

        $cacheEntries = self::$queryCache->getValues();
        self::assertCount(1, $cacheEntries);
    }

    public function testSubqueryLimitAndOffsetAreIgnored(): void
    {
        // Not sure what to do about this test. Basically, I want to make sure that
        // firstResult/maxResult for subqueries are not relevant, they do not make it
        // into the final query at all. That would give us the guarantee that the
        // "sql finalizer" step is sufficient for the final, "outer" query and we
        // do not need to run finalizers for the subqueries.

        // This DQL/query makes no sense, it's just about creating a subquery in the first place
        $queryBuilder = $this->_em->createQueryBuilder();
        $queryBuilder
            ->select('o')
            ->from(CmsUser::class, 'o')
            ->where($queryBuilder->expr()->exists(
                $this->_em->createQueryBuilder()
                            ->select('u')
                            ->from(CmsUser::class, 'u')
                            ->setFirstResult(10)
                            ->setMaxResults(20),
            ));

        $query       = $queryBuilder->getQuery();
        $originalSql = $query->getSQL();

        $clone = clone $query;
        $clone->setFirstResult(24);
        $clone->setMaxResults(42);
        $limitedSql = $clone->getSQL();

        $platform = $this->_em->getConnection()->getDatabasePlatform();

        // The SQL is platform specific and may even be something with outer SELECTS being added. So,
        // derive the expected value at runtime through the platform.
        self::assertSame($platform->modifyLimitQuery($originalSql, 42, 24), $limitedSql);
    }
}
