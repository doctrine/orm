<?php

declare(strict_types=1);

namespace Doctrine\Tests\ORM\Functional;

use Closure;
use Doctrine\ORM\EntityManagerInterface;
use Doctrine\ORM\Query;
use Doctrine\ORM\QueryBuilder;
use Doctrine\ORM\Tools\Pagination\Paginator;
use Doctrine\Tests\Models\CMS\CmsAddress;
use Doctrine\Tests\Models\CMS\CmsArticle;
use Doctrine\Tests\Models\CMS\CmsArticleAssociatedDetail;
use Doctrine\Tests\Models\CMS\CmsComment;
use Doctrine\Tests\Models\CMS\CmsUser;
use Doctrine\Tests\Models\CompositeKeyRelations\CustomerClass;
use Doctrine\Tests\Models\CompositeKeyRelations\InvoiceClass;
use Doctrine\Tests\OrmFunctionalTestCase;
use PHPUnit\Framework\Attributes\DataProvider;

use function iterator_to_array;
use function min;

class PaginatorAutoDetectionTest extends OrmFunctionalTestCase
{
    private const RESULT_QUERY_FAST                           = 'result-query-fast';
    private const RESULT_QUERY_WITH_SUBQUERY_TREE_WALKER      = 'result-query-with-subquery-tree-walker';
    private const RESULT_QUERY_WITH_SUBQUERY_OUTPUT_WALKER    = 'result-query-with-subquery-output-walker';
    private const COUNT_QUERY_FAST                            = 'count-query-fast';
    private const COUNT_QUERY_FAST_WITH_DISTINCT              = 'count-query-fast-with-distinct';
    private const COUNT_QUERY_FAST_NO_DISTINCT                = 'count-query-fast-no-distinct';
    private const COUNT_QUERY_FAST_SHOULD_DROP_GROUP_BY_TRUE  = 'count-query-fast-should-drop-group-by-true';
    private const COUNT_QUERY_FAST_SHOULD_DROP_GROUP_BY_FALSE = 'count-query-fast-should-drop-group-by-false';
    private const COUNT_QUERY_WITH_OUTPUT_WALKER              = 'count-query-with-output-walker';

    protected function setUp(): void
    {
        $this->useModelSet('cms');
        $this->useModelSet('compositekeyrelations');

        parent::setUp();

        $this->populate();
    }

    /** @param Closure(self, EntityManagerInterface): (Query|QueryBuilder) $testSetUpAndQueryFactory */
    #[DataProvider('provideQueriesForNewWithAutoDetection')]
    public function testNewWithAutoDetection(
        Closure $testSetUpAndQueryFactory,
        string $expectedResultQueryKind,
        string $expectedCountQueryKind,
        string $expectedFastCountQueryDistinctUsage,
        string $expectedFastCountQueryShouldDropGroupBy,
        int $expectedCount,
    ): void {
        $paginator = Paginator::newWithAutoDetection($testSetUpAndQueryFactory($this, $this->_em));

        // Walker-selection assertions
        self::assertSame(
            $expectedResultQueryKind,
            match (true) {
                ! $paginator->getQueryProducesDuplicates() => self::RESULT_QUERY_FAST,
                $paginator->getUseResultQueryOutputWalker() === false => self::RESULT_QUERY_WITH_SUBQUERY_TREE_WALKER,
                default => self::RESULT_QUERY_WITH_SUBQUERY_OUTPUT_WALKER,
            },
        );
        self::assertSame(
            $expectedCountQueryKind,
            match ($paginator->getUseCountQueryOutputWalker()) {
                true => self::COUNT_QUERY_WITH_OUTPUT_WALKER,
                false => self::COUNT_QUERY_FAST,
                null => null,
            },
        );
        self::assertSame(
            $expectedFastCountQueryDistinctUsage,
            $paginator->getDefaultCountWalkerNeedsToUseCountDistinct()
                ? self::COUNT_QUERY_FAST_WITH_DISTINCT
                : self::COUNT_QUERY_FAST_NO_DISTINCT,
        );
        self::assertSame(
            $expectedFastCountQueryShouldDropGroupBy,
            $paginator->getCountWalkerShouldDropGroupByClause()
                ? self::COUNT_QUERY_FAST_SHOULD_DROP_GROUP_BY_TRUE
                : self::COUNT_QUERY_FAST_SHOULD_DROP_GROUP_BY_FALSE,
        );

        // Sanity-check the pagination results
        self::assertSame($expectedCount, $paginator->count());
        self::assertCount(min($expectedCount, 10), iterator_to_array($paginator->getIterator()));
    }

    /** @return iterable<string, array{Closure, string, string, string, string, int}> */
    public static function provideQueriesForNewWithAutoDetection(): iterable
    {
        yield 'QueryBuilder is supported' => [
            static function (self $testCase, EntityManagerInterface $em): QueryBuilder {
                $queryBuilder = $em->createQueryBuilder()
                    ->select('u')
                    ->from(CmsUser::class, 'u')
                    ->orderBy('u.id');
                $queryBuilder->setMaxResults(10);

                return $queryBuilder;
            },
            self::RESULT_QUERY_FAST,
            self::COUNT_QUERY_FAST,
            self::COUNT_QUERY_FAST_NO_DISTINCT,
            self::COUNT_QUERY_FAST_SHOULD_DROP_GROUP_BY_FALSE,
            9,
        ];

        yield 'query with no joins' => [
            static function (self $testCase, EntityManagerInterface $em): Query {
                $query = $em->createQuery('SELECT u FROM ' . CmsUser::class . ' u ORDER BY u.id');
                $query->setMaxResults(10);

                return $query;
            },
            self::RESULT_QUERY_FAST,
            self::COUNT_QUERY_FAST,
            self::COUNT_QUERY_FAST_NO_DISTINCT,
            self::COUNT_QUERY_FAST_SHOULD_DROP_GROUP_BY_FALSE,
            9,
        ];

        yield 'query with no joins, composite primary key' => [
            static function (self $testCase, EntityManagerInterface $em): Query {
                $query = $em->createQuery('SELECT c FROM ' . CustomerClass::class . ' c ORDER BY c.companyCode');
                $query->setMaxResults(10);

                return $query;
            },
            self::RESULT_QUERY_FAST,
            self::COUNT_QUERY_FAST,
            self::COUNT_QUERY_FAST_NO_DISTINCT,
            self::COUNT_QUERY_FAST_SHOULD_DROP_GROUP_BY_FALSE,
            5,
        ];

        yield 'query with OneToOne join' => [
            static function (self $testCase, EntityManagerInterface $em): Query {
                $query = $em->createQuery('SELECT u, a FROM ' . CmsUser::class . ' u JOIN u.address a ORDER BY u.id');
                $query->setMaxResults(10);

                return $query;
            },
            self::RESULT_QUERY_FAST,
            self::COUNT_QUERY_FAST,
            self::COUNT_QUERY_FAST_NO_DISTINCT,
            self::COUNT_QUERY_FAST_SHOULD_DROP_GROUP_BY_FALSE,
            9,
        ];

        yield 'query with OneToMany join' => [
            static function (self $testCase, EntityManagerInterface $em): Query {
                $query = $em->createQuery('SELECT u, a FROM ' . CmsUser::class . ' u JOIN u.articles a ORDER BY u.id');
                $query->setMaxResults(10);

                return $query;
            },
            self::RESULT_QUERY_WITH_SUBQUERY_TREE_WALKER,
            self::COUNT_QUERY_FAST,
            self::COUNT_QUERY_FAST_WITH_DISTINCT,
            self::COUNT_QUERY_FAST_SHOULD_DROP_GROUP_BY_FALSE,
            9,
        ];

        yield 'query with OneToMany join, select scalars only' => [
            static function (self $testCase, EntityManagerInterface $em): Query {
                $query = $em->createQuery('SELECT u.id, a.id FROM ' . CmsUser::class . ' u JOIN u.articles a ORDER BY u.id');
                $query->setMaxResults(10);

                return $query;
            },
            self::RESULT_QUERY_FAST,
            self::COUNT_QUERY_FAST,
            self::COUNT_QUERY_FAST_NO_DISTINCT,
            self::COUNT_QUERY_FAST_SHOULD_DROP_GROUP_BY_FALSE,
            45,
        ];

        yield 'query with OneToMany join, select scalars only, select only ToOne' => [
            static function (self $testCase, EntityManagerInterface $em): Query {
                $query = $em->createQuery('SELECT u.id FROM ' . CmsUser::class . ' u JOIN u.articles a ORDER BY u.id');
                $query->setMaxResults(10);

                return $query;
            },
            self::RESULT_QUERY_FAST,
            self::COUNT_QUERY_FAST,
            self::COUNT_QUERY_FAST_NO_DISTINCT,
            self::COUNT_QUERY_FAST_SHOULD_DROP_GROUP_BY_FALSE,
            // It returns this many records because the query does not do SELECT DISTINCT on its own.
            45,
        ];

        yield 'query with OneToMany join, select scalar DISTINCT' => [
            static function (self $testCase, EntityManagerInterface $em): Query {
                $query = $em->createQuery('SELECT DISTINCT u.id, a.id FROM ' . CmsUser::class . ' u JOIN u.articles a ORDER BY u.id');
                $query->setMaxResults(10);

                // This case isn't in fact supported even by the "compatible" queries. At least assert that the test
                // fails for a consistent reason.
                $testCase->expectExceptionMessageMatches('/^Not all identifier properties can be found in the ResultSetMapping:/');

                return $query;
            },
            self::RESULT_QUERY_WITH_SUBQUERY_TREE_WALKER,
            self::COUNT_QUERY_WITH_OUTPUT_WALKER,
            self::COUNT_QUERY_FAST_WITH_DISTINCT,
            self::COUNT_QUERY_FAST_SHOULD_DROP_GROUP_BY_FALSE,
            45,
        ];

        // A ToOne relation that is a descendant of a ToMany relation, is effectively a ToMany relation.
        yield 'query with OneToMany join, fetch-join a deep ToMany-dependant ToOne relation' => [
            static function (self $testCase, EntityManagerInterface $em): Query {
                $query = $em->createQuery('SELECT u, comment_author.id FROM ' . CmsUser::class . ' u JOIN u.articles a JOIN a.comments c JOIN c.user comment_author ORDER BY u.id');
                $query->setMaxResults(10);

                return $query;
            },
            self::RESULT_QUERY_WITH_SUBQUERY_TREE_WALKER,
            self::COUNT_QUERY_FAST,
            self::COUNT_QUERY_FAST_WITH_DISTINCT,
            self::COUNT_QUERY_FAST_SHOULD_DROP_GROUP_BY_FALSE,
            5,
        ];

        yield 'query with OneToMany join, not fetch-joined' => [
            static function (self $testCase, EntityManagerInterface $em): Query {
                $query = $em->createQuery('SELECT u FROM ' . CmsUser::class . ' u JOIN u.articles a ORDER BY u.id');
                $query->setMaxResults(10);

                return $query;
            },
            self::RESULT_QUERY_WITH_SUBQUERY_TREE_WALKER,
            self::COUNT_QUERY_FAST,
            self::COUNT_QUERY_FAST_WITH_DISTINCT,
            self::COUNT_QUERY_FAST_SHOULD_DROP_GROUP_BY_FALSE,
            9,
        ];

        yield 'query with OneToMany join, not fetch-joined, with DISTINCT' => [
            static function (self $testCase, EntityManagerInterface $em): Query {
                $query = $em->createQuery('SELECT DISTINCT u FROM ' . CmsUser::class . ' u JOIN u.articles a ORDER BY u.id');
                $query->setMaxResults(10);

                return $query;
            },
            self::RESULT_QUERY_FAST,
            self::COUNT_QUERY_FAST,
            self::COUNT_QUERY_FAST_WITH_DISTINCT,
            self::COUNT_QUERY_FAST_SHOULD_DROP_GROUP_BY_FALSE,
            9,
        ];

        yield 'query with OneToMany join, not fetch-joined, with DISTINCT, composite primary key' => [
            static function (self $testCase, EntityManagerInterface $em): Query {
                $query = $em->createQuery('SELECT DISTINCT c FROM ' . CustomerClass::class . ' c JOIN c.invoices i ORDER BY c.companyCode');
                $query->setMaxResults(10);

                return $query;
            },
            self::RESULT_QUERY_FAST,
            self::COUNT_QUERY_WITH_OUTPUT_WALKER,
            self::COUNT_QUERY_FAST_WITH_DISTINCT,
            self::COUNT_QUERY_FAST_SHOULD_DROP_GROUP_BY_FALSE,
            5,
        ];

        yield 'query with OneToMany join, not fetch-joined, with DISTINCT, with path expression' => [
            static function (self $testCase, EntityManagerInterface $em): Query {
                $query = $em->createQuery('SELECT DISTINCT u.id FROM ' . CmsUser::class . ' u JOIN u.articles a ORDER BY u.id');
                $query->setMaxResults(10);

                return $query;
            },
            self::RESULT_QUERY_FAST,
            self::COUNT_QUERY_FAST,
            self::COUNT_QUERY_FAST_WITH_DISTINCT,
            self::COUNT_QUERY_FAST_SHOULD_DROP_GROUP_BY_FALSE,
            9,
        ];

        yield 'query with complex select expression, no ToMany joins' => [
            static function (self $testCase, EntityManagerInterface $em): Query {
                $query = $em->createQuery('SELECT u, (CASE WHEN u.id < 5 THEN 1 ELSE 0 END) AS complexExpression FROM ' . CmsUser::class . ' u ORDER BY u.id');
                $query->setMaxResults(10);

                return $query;
            },
            self::RESULT_QUERY_FAST,
            self::COUNT_QUERY_FAST,
            self::COUNT_QUERY_FAST_NO_DISTINCT,
            self::COUNT_QUERY_FAST_SHOULD_DROP_GROUP_BY_FALSE,
            9,
        ];

        yield 'query with complex select expression, no columns from the ToMany join' => [
            static function (self $testCase, EntityManagerInterface $em): Query {
                $query = $em->createQuery('SELECT u, (CASE WHEN u.id < 5 THEN 1 ELSE 0 END) AS complexExpression FROM ' . CmsUser::class . ' u JOIN u.articles a ORDER BY u.id');
                $query->setMaxResults(10);

                return $query;
            },
            self::RESULT_QUERY_WITH_SUBQUERY_TREE_WALKER,
            self::COUNT_QUERY_FAST,
            self::COUNT_QUERY_FAST_WITH_DISTINCT,
            self::COUNT_QUERY_FAST_SHOULD_DROP_GROUP_BY_FALSE,
            9,
        ];

        yield 'query with complex select expression, no columns from the ToMany join, with DISTINCT' => [
            static function (self $testCase, EntityManagerInterface $em): Query {
                $query = $em->createQuery('SELECT DISTINCT u, (CASE WHEN u.id < 5 THEN 1 ELSE 0 END) AS complexExpression FROM ' . CmsUser::class . ' u JOIN u.articles a ORDER BY u.id');
                $query->setMaxResults(10);

                return $query;
            },
            self::RESULT_QUERY_FAST,
            self::COUNT_QUERY_FAST,
            self::COUNT_QUERY_FAST_WITH_DISTINCT,
            self::COUNT_QUERY_FAST_SHOULD_DROP_GROUP_BY_FALSE,
            9,
        ];

        yield 'query with complex select expression, with columns from the ToMany join' => [
            static function (self $testCase, EntityManagerInterface $em): Query {
                $query = $em->createQuery('SELECT u, (CASE WHEN a.id < 5 THEN 1 ELSE 0 END) AS complexExpression FROM ' . CmsUser::class . ' u JOIN u.articles a ORDER BY u.id');
                $query->setMaxResults(10);

                return $query;
            },
            self::RESULT_QUERY_WITH_SUBQUERY_TREE_WALKER,
            self::COUNT_QUERY_FAST,
            self::COUNT_QUERY_FAST_WITH_DISTINCT,
            self::COUNT_QUERY_FAST_SHOULD_DROP_GROUP_BY_FALSE,
            // This should really return either 45 or 10 rows. The scalar result is 45 rows. User with id 3 has 2 distinct
            // rows: one with complexExpression=1 and one with complexExpression=0, so it could have been expected to either
            // get 10 result rows here, or 9 rows with the row for User3 with complexExpression column being an array of
            // 2 values. But what happens is that Paginator drops one of the User3 result rows.
            //
            // It's not clear what the actual expectation and support is for queries that select an entity and a scalar
            // from a ToMany relation.
            //
            // The same "dilemma" happens in similar subsequent test cases.
            9,
        ];

        yield 'query with complex select expression, with columns from the ToMany join, with DISTINCT' => [
            static function (self $testCase, EntityManagerInterface $em): Query {
                $query = $em->createQuery('SELECT DISTINCT u, (CASE WHEN SUBSTRING(a.text, LENGTH(a.text), 1) = \'2\' THEN 1 ELSE 0 END) AS complexExpression FROM ' . CmsUser::class . ' u JOIN u.articles a ORDER BY u.id');
                $query->setMaxResults(10);

                return $query;
            },
            self::RESULT_QUERY_WITH_SUBQUERY_TREE_WALKER,
            self::COUNT_QUERY_WITH_OUTPUT_WALKER,
            self::COUNT_QUERY_FAST_WITH_DISTINCT,
            self::COUNT_QUERY_FAST_SHOULD_DROP_GROUP_BY_FALSE,
            // Note: this query produces 16 result rows but for some reason both the "with subquery" result queries and
            // the "with output walker" count query produce 9, which is not expected. If this ever gets "fixed", then
            // the following test assertion could be restored.
            // 16,
            9,
        ];

        // Note: sub-selects in the select expression will never result in "duplicate rows". The DISTINCT is needed
        // only because the main query still joins on a ToMany relation.
        yield 'query with sub-select select expression, no columns from the ToMany join, with DISTINCT' => [
            static function (self $testCase, EntityManagerInterface $em): Query {
                $query = $em->createQuery('SELECT DISTINCT u, (SELECT MAX(a_inner.version) FROM ' . CmsArticle::class . ' a_inner WHERE a_inner.user = u) AS complexExpression FROM ' . CmsUser::class . ' u JOIN u.articles a ORDER BY u.id');
                $query->setMaxResults(10);

                return $query;
            },
            self::RESULT_QUERY_FAST,
            self::COUNT_QUERY_FAST,
            self::COUNT_QUERY_FAST_WITH_DISTINCT,
            self::COUNT_QUERY_FAST_SHOULD_DROP_GROUP_BY_FALSE,
            9,
        ];

        // The auto-detection should not process select expressions that are too complex. Instead, it should assume
        // the worst case scenario and use the "slower but more compatible" result query.
        yield 'query with too complex select expression' => [
            static function (self $testCase, EntityManagerInterface $em): Query {
                $query = $em->createQuery(<<<'DQL'
                    SELECT
                        u,
                        (
                            CASE WHEN u.id > 0 THEN
                                CASE WHEN u.id > 1 THEN
                                    CASE WHEN u.id > 2 THEN
                                        CASE WHEN u.id > 3 THEN
                                            CASE WHEN u.id > 4 THEN
                                                CASE WHEN u.id > 5 THEN
                                                    CASE WHEN u.id > 6 THEN
                                                        CASE WHEN u.id > 7 THEN
                                                            'deep'
                                                        ELSE 'x7' END
                                                    ELSE 'x6' END
                                                ELSE 'x5' END
                                            ELSE 'x4' END
                                        ELSE 'x3' END
                                    ELSE 'x2' END
                                ELSE 'x1' END
                            ELSE 'x0' END
                        ) AS ridiculousValue
                    FROM Doctrine\Tests\Models\CMS\CmsUser u
                    JOIN u.articles a
                    ORDER BY u.id
                DQL);
                $query->setMaxResults(10);

                return $query;
            },
            self::RESULT_QUERY_WITH_SUBQUERY_TREE_WALKER,
            self::COUNT_QUERY_FAST,
            self::COUNT_QUERY_FAST_WITH_DISTINCT,
            self::COUNT_QUERY_FAST_SHOULD_DROP_GROUP_BY_FALSE,
            9,
        ];

        // Queries that only select aggregate functions are pointless to paginate (because they return only 1 row)
        // but they are still valid cases. They should always be run using the "slow but compatible" pagination queries.
        yield 'query selects only aggregate functions' => [
            static function (self $testCase, EntityManagerInterface $em): Query {
                $query = $em->createQuery('SELECT MAX(u.id) FROM ' . CmsUser::class . ' u');
                $query->setMaxResults(10);

                // This case isn't in fact supported even by the "compatible" queries. At least assert that the test
                // fails for a consistent reason.
                $testCase->expectExceptionMessageMatches('/^Not all identifier properties can be found in the ResultSetMapping:/');

                return $query;
            },
            self::RESULT_QUERY_WITH_SUBQUERY_OUTPUT_WALKER,
            self::COUNT_QUERY_WITH_OUTPUT_WALKER,
            self::COUNT_QUERY_FAST_WITH_DISTINCT,
            self::COUNT_QUERY_FAST_SHOULD_DROP_GROUP_BY_FALSE,
            1,
        ];

        // This case is the "false positive" detection of the "only aggregate functions" detection.
        yield 'query selects only function-like expressions' => [
            static function (self $testCase, EntityManagerInterface $em): Query {
                $query = $em->createQuery('SELECT CONCAT(\'Test: \', u.id) FROM ' . CmsUser::class . ' u');
                $query->setMaxResults(10);

                // This case isn't in fact supported even by the "compatible" queries. At least assert that the test
                // fails for a consistent reason.
                $testCase->expectExceptionMessageMatches('/^Not all identifier properties can be found in the ResultSetMapping:/');

                return $query;
            },
            self::RESULT_QUERY_WITH_SUBQUERY_OUTPUT_WALKER,
            self::COUNT_QUERY_WITH_OUTPUT_WALKER,
            self::COUNT_QUERY_FAST_WITH_DISTINCT,
            self::COUNT_QUERY_FAST_SHOULD_DROP_GROUP_BY_FALSE,
            9,
        ];

        // A "no ToMany" GroupBy case is a bit pointless but it's still a use case.
        yield 'query with GroupBy, no ToMany joins, group by entity alias' => [
            static function (self $testCase, EntityManagerInterface $em): Query {
                $query = $em->createQuery('SELECT u FROM ' . CmsUser::class . ' u GROUP BY u ORDER BY u.id');
                $query->setMaxResults(10);

                return $query;
            },
            self::RESULT_QUERY_FAST,
            self::COUNT_QUERY_FAST,
            self::COUNT_QUERY_FAST_NO_DISTINCT,
            self::COUNT_QUERY_FAST_SHOULD_DROP_GROUP_BY_TRUE,
            9,
        ];

        yield 'query with GroupBy, no ToMany joins, group by Select alias on an id column' => [
            static function (self $testCase, EntityManagerInterface $em): Query {
                $query = $em->createQuery('SELECT u.id AS my_result FROM ' . CmsUser::class . ' u GROUP BY my_result ORDER BY u.id');
                $query->setMaxResults(10);

                return $query;
            },
            self::RESULT_QUERY_FAST,
            self::COUNT_QUERY_FAST,
            self::COUNT_QUERY_FAST_NO_DISTINCT,
            self::COUNT_QUERY_FAST_SHOULD_DROP_GROUP_BY_TRUE,
            9,
        ];

        yield 'query with GroupBy, no ToMany joins, group by Select alias on a unique, not nullable column' => [
            static function (self $testCase, EntityManagerInterface $em): Query {
                $query = $em->createQuery('SELECT u.username AS my_result FROM ' . CmsUser::class . ' u GROUP BY my_result ORDER BY u.id');
                $query->setMaxResults(10);

                return $query;
            },
            self::RESULT_QUERY_FAST,
            self::COUNT_QUERY_FAST,
            self::COUNT_QUERY_FAST_NO_DISTINCT,
            self::COUNT_QUERY_FAST_SHOULD_DROP_GROUP_BY_TRUE,
            9,
        ];

        yield 'query with GroupBy, no ToMany joins, group by Select alias on any other column' => [
            static function (self $testCase, EntityManagerInterface $em): Query {
                $query = $em->createQuery('SELECT u.status AS my_result, COUNT(u) FROM ' . CmsUser::class . ' u GROUP BY my_result ORDER BY u.id');
                $query->setMaxResults(10);

                return $query;
            },
            self::RESULT_QUERY_FAST,
            self::COUNT_QUERY_WITH_OUTPUT_WALKER,
            self::COUNT_QUERY_FAST_NO_DISTINCT,
            self::COUNT_QUERY_FAST_SHOULD_DROP_GROUP_BY_FALSE,
            2,
        ];

        yield 'query with GroupBy, no ToMany joins, group by an id column' => [
            static function (self $testCase, EntityManagerInterface $em): Query {
                $query = $em->createQuery('SELECT u.id FROM ' . CmsUser::class . ' u GROUP BY u.id ORDER BY u.id');
                $query->setMaxResults(10);

                return $query;
            },
            self::RESULT_QUERY_FAST,
            self::COUNT_QUERY_FAST,
            self::COUNT_QUERY_FAST_NO_DISTINCT,
            self::COUNT_QUERY_FAST_SHOULD_DROP_GROUP_BY_TRUE,
            9,
        ];

        yield 'query with GroupBy, no ToMany joins, group by a unique, not nullable column' => [
            static function (self $testCase, EntityManagerInterface $em): Query {
                $query = $em->createQuery('SELECT u.username FROM ' . CmsUser::class . ' u GROUP BY u.username ORDER BY u.id');
                $query->setMaxResults(10);

                return $query;
            },
            self::RESULT_QUERY_FAST,
            self::COUNT_QUERY_FAST,
            self::COUNT_QUERY_FAST_NO_DISTINCT,
            self::COUNT_QUERY_FAST_SHOULD_DROP_GROUP_BY_TRUE,
            9,
        ];

        yield 'query with GroupBy, no ToMany joins, group by any other column' => [
            static function (self $testCase, EntityManagerInterface $em): Query {
                $query = $em->createQuery('SELECT u.status, COUNT(u) FROM ' . CmsUser::class . ' u GROUP BY u.status ORDER BY u.id');
                $query->setMaxResults(10);

                return $query;
            },
            self::RESULT_QUERY_FAST,
            self::COUNT_QUERY_WITH_OUTPUT_WALKER,
            self::COUNT_QUERY_FAST_NO_DISTINCT,
            self::COUNT_QUERY_FAST_SHOULD_DROP_GROUP_BY_FALSE,
            2,
        ];

        yield 'query with GroupBy, no ToMany joins, group by an id column, composite primary key, partial key grouped' => [
            static function (self $testCase, EntityManagerInterface $em): Query {
                $query = $em->createQuery('SELECT c.companyCode, COUNT(c) FROM ' . CustomerClass::class . ' c GROUP BY c.companyCode ORDER BY c.companyCode');
                $query->setMaxResults(10);

                return $query;
            },
            self::RESULT_QUERY_FAST,
            self::COUNT_QUERY_WITH_OUTPUT_WALKER,
            self::COUNT_QUERY_FAST_NO_DISTINCT,
            self::COUNT_QUERY_FAST_SHOULD_DROP_GROUP_BY_FALSE,
            2,
        ];

        yield 'query with GroupBy, no ToMany joins, group by an id column, composite primary key, full key grouped' => [
            static function (self $testCase, EntityManagerInterface $em): Query {
                $query = $em->createQuery('SELECT c.name FROM ' . CustomerClass::class . ' c GROUP BY c.companyCode, c.code ORDER BY c.companyCode');
                $query->setMaxResults(10);

                return $query;
            },
            self::RESULT_QUERY_FAST,
            self::COUNT_QUERY_FAST,
            self::COUNT_QUERY_FAST_NO_DISTINCT,
            self::COUNT_QUERY_FAST_SHOULD_DROP_GROUP_BY_TRUE,
            5,
        ];

        yield 'query with GroupBy, with ToMany joins, group by root id column' => [
            static function (self $testCase, EntityManagerInterface $em): Query {
                $query = $em->createQuery('SELECT u.id FROM ' . CmsUser::class . ' u JOIN u.articles a GROUP BY u.id ORDER BY u.id');
                $query->setMaxResults(10);

                return $query;
            },
            self::RESULT_QUERY_FAST,
            self::COUNT_QUERY_FAST,
            self::COUNT_QUERY_FAST_WITH_DISTINCT,
            self::COUNT_QUERY_FAST_SHOULD_DROP_GROUP_BY_TRUE,
            9,
        ];

        yield 'query with GroupBy, with ToMany joins, select COUNT(ToMany), group by root id column' => [
            static function (self $testCase, EntityManagerInterface $em): Query {
                $query = $em->createQuery('SELECT u.id, COUNT(a) FROM ' . CmsUser::class . ' u JOIN u.articles a GROUP BY u.id ORDER BY u.id');
                $query->setMaxResults(10);

                return $query;
            },
            self::RESULT_QUERY_FAST,
            self::COUNT_QUERY_FAST,
            self::COUNT_QUERY_FAST_WITH_DISTINCT,
            self::COUNT_QUERY_FAST_SHOULD_DROP_GROUP_BY_TRUE,
            9,
        ];

        // Note how this cannot use the "fast" count query, because the entity has a composite primary key, for
        // which a fast count query would need to run COUNT(DISTINCT id) (instead of COUNT(*)), which it cannot do.
        yield 'query with GroupBy, with ToMany joins, group by root id column, composite primary key, full key grouped' => [
            static function (self $testCase, EntityManagerInterface $em): Query {
                $query = $em->createQuery('SELECT c FROM ' . CustomerClass::class . ' c JOIN c.invoices i GROUP BY c.companyCode, c.code ORDER BY c.companyCode');
                $query->setMaxResults(10);

                return $query;
            },
            self::RESULT_QUERY_FAST,
            self::COUNT_QUERY_WITH_OUTPUT_WALKER,
            self::COUNT_QUERY_FAST_WITH_DISTINCT,
            self::COUNT_QUERY_FAST_SHOULD_DROP_GROUP_BY_FALSE,
            5,
        ];

        yield 'query with GroupBy, with ToMany joins, group by ToMany column' => [
            static function (self $testCase, EntityManagerInterface $em): Query {
                $query = $em->createQuery('SELECT a.id, COUNT(u.id) FROM ' . CmsUser::class . ' u JOIN u.articles a GROUP BY a.id ORDER BY a.id');
                $query->setMaxResults(10);

                // The "default" LimitSubqueryOutputWalker result subquery doesn't in fact support running this query.
                $testCase->expectExceptionMessageMatches('/^The Paginator does not support Queries which only yield ScalarResults/');

                return $query;
            },
            self::RESULT_QUERY_WITH_SUBQUERY_OUTPUT_WALKER,
            self::COUNT_QUERY_WITH_OUTPUT_WALKER,
            self::COUNT_QUERY_FAST_WITH_DISTINCT,
            self::COUNT_QUERY_FAST_SHOULD_DROP_GROUP_BY_FALSE,
            45,
        ];

        // Note: this use case doesn't use the fast count query because it groups by an expression, and the auto-detection
        // feature does not even attempt to find out "what the expressions do".
        yield 'query with GroupBy, with ToMany joins, select COUNT(ToMany), group by Select expression not using ToMany columns' => [
            static function (self $testCase, EntityManagerInterface $em): Query {
                $query = $em->createQuery('SELECT CONCAT(\'lorem ipsum: \', u.id) AS my_result, u.username, COUNT(a) FROM ' . CmsUser::class . ' u JOIN u.articles a GROUP BY my_result ORDER BY my_result');
                $query->setMaxResults(10);

                return $query;
            },
            self::RESULT_QUERY_FAST,
            self::COUNT_QUERY_WITH_OUTPUT_WALKER,
            self::COUNT_QUERY_FAST_WITH_DISTINCT,
            self::COUNT_QUERY_FAST_SHOULD_DROP_GROUP_BY_FALSE,
            9,
        ];

        yield 'query with GroupBy, with ToMany joins, group by Select expression using ToMany columns' => [
            static function (self $testCase, EntityManagerInterface $em): Query {
                $query = $em->createQuery('SELECT CONCAT(\'lorem ipsum: \', a.id) AS my_result, COUNT(u) FROM ' . CmsUser::class . ' u JOIN u.articles a GROUP BY my_result ORDER BY my_result');
                $query->setMaxResults(10);

                // The "default" LimitSubqueryOutputWalker result subquery doesn't in fact support running this query.
                $testCase->expectExceptionMessageMatches('/^The Paginator does not support Queries which only yield ScalarResults/');

                return $query;
            },
            self::RESULT_QUERY_WITH_SUBQUERY_OUTPUT_WALKER,
            self::COUNT_QUERY_WITH_OUTPUT_WALKER,
            self::COUNT_QUERY_FAST_WITH_DISTINCT,
            self::COUNT_QUERY_FAST_SHOULD_DROP_GROUP_BY_FALSE,
            45,
        ];

        yield 'query with Having' => [
            static function (self $testCase, EntityManagerInterface $em): Query {
                $query = $em->createQuery('SELECT u, COUNT(a) articles_count FROM ' . CmsUser::class . ' u JOIN u.articles a GROUP BY u.id HAVING articles_count > 2 ORDER BY u.id');
                $query->setMaxResults(10);

                return $query;
            },
            self::RESULT_QUERY_FAST,
            self::COUNT_QUERY_WITH_OUTPUT_WALKER,
            self::COUNT_QUERY_FAST_WITH_DISTINCT,
            self::COUNT_QUERY_FAST_SHOULD_DROP_GROUP_BY_FALSE,
            7,
        ];

        yield 'query with multiple Froms' => [
            static function (self $testCase, EntityManagerInterface $em): Query {
                $query = $em->createQuery('SELECT u, a FROM ' . CmsUser::class . ' u, ' . CmsArticle::class . ' a ORDER BY u.id');
                $query->setMaxResults(10);

                // Neither CountWalker nor CountOutputWalker supports this query.
                $testCase->expectExceptionMessageMatches('/^Cannot count query which selects two FROM components/');

                return $query;
            },
            self::RESULT_QUERY_WITH_SUBQUERY_OUTPUT_WALKER,
            self::COUNT_QUERY_WITH_OUTPUT_WALKER,
            self::COUNT_QUERY_FAST_WITH_DISTINCT,
            self::COUNT_QUERY_FAST_SHOULD_DROP_GROUP_BY_FALSE,
            9,
        ];

        yield 'query with OneToMany join, no OrderBy' => [
            static function (self $testCase, EntityManagerInterface $em): Query {
                $query = $em->createQuery('SELECT u, a FROM ' . CmsUser::class . ' u JOIN u.articles a');
                $query->setMaxResults(10);

                return $query;
            },
            self::RESULT_QUERY_WITH_SUBQUERY_TREE_WALKER,
            self::COUNT_QUERY_FAST,
            self::COUNT_QUERY_FAST_WITH_DISTINCT,
            self::COUNT_QUERY_FAST_SHOULD_DROP_GROUP_BY_FALSE,
            9,
        ];

        yield 'query with OneToMany join, root entity uses a primary key that is a foreign key' => [
            static function (self $testCase, EntityManagerInterface $em): Query {
                $query = $em->createQuery('SELECT ad, a, c FROM ' . CmsArticleAssociatedDetail::class . ' ad JOIN ad.article a JOIN a.comments c');
                $query->setMaxResults(10);

                return $query;
            },
            self::RESULT_QUERY_WITH_SUBQUERY_OUTPUT_WALKER,
            self::COUNT_QUERY_FAST,
            self::COUNT_QUERY_FAST_WITH_DISTINCT,
            self::COUNT_QUERY_FAST_SHOULD_DROP_GROUP_BY_FALSE,
            5,
        ];

        yield 'query with OneToMany join, order by ToMany column' => [
            static function (self $testCase, EntityManagerInterface $em): Query {
                $query = $em->createQuery('SELECT u, a FROM ' . CmsUser::class . ' u JOIN u.articles a ORDER BY a.id');
                $query->setMaxResults(10);

                return $query;
            },
            self::RESULT_QUERY_WITH_SUBQUERY_OUTPUT_WALKER,
            self::COUNT_QUERY_FAST,
            self::COUNT_QUERY_FAST_WITH_DISTINCT,
            self::COUNT_QUERY_FAST_SHOULD_DROP_GROUP_BY_FALSE,
            9,
        ];

        yield 'query with OneToMany join, order by ToOne Select alias (expression), Select scalars only' => [
            static function (self $testCase, EntityManagerInterface $em): Query {
                $query = $em->createQuery('SELECT CONCAT(\'Test: \', u.id) AS my_result, a.id FROM ' . CmsUser::class . ' u JOIN u.articles a ORDER BY my_result');
                $query->setMaxResults(10);

                return $query;
            },
            self::RESULT_QUERY_FAST,
            self::COUNT_QUERY_FAST,
            self::COUNT_QUERY_FAST_NO_DISTINCT,
            self::COUNT_QUERY_FAST_SHOULD_DROP_GROUP_BY_FALSE,
            45,
        ];

        yield 'query with OneToMany join, order by ToOne Select alias (expression)' => [
            static function (self $testCase, EntityManagerInterface $em): Query {
                $query = $em->createQuery('SELECT u, CONCAT(\'Test: \', u.id) AS my_result, a.id FROM ' . CmsUser::class . ' u JOIN u.articles a ORDER BY my_result');
                $query->setMaxResults(10);

                return $query;
            },
            self::RESULT_QUERY_WITH_SUBQUERY_TREE_WALKER,
            self::COUNT_QUERY_FAST,
            self::COUNT_QUERY_FAST_WITH_DISTINCT,
            self::COUNT_QUERY_FAST_SHOULD_DROP_GROUP_BY_FALSE,
            9,
        ];

        yield 'query with OneToMany join, order by ToMany Select alias (expression)' => [
            static function (self $testCase, EntityManagerInterface $em): Query {
                $query = $em->createQuery('SELECT u, CONCAT(\'Test: \', a.id) AS my_result FROM ' . CmsUser::class . ' u JOIN u.articles a ORDER BY my_result');
                $query->setMaxResults(10);

                return $query;
            },
            self::RESULT_QUERY_WITH_SUBQUERY_OUTPUT_WALKER,
            self::COUNT_QUERY_FAST,
            self::COUNT_QUERY_FAST_WITH_DISTINCT,
            self::COUNT_QUERY_FAST_SHOULD_DROP_GROUP_BY_FALSE,
            9,
        ];

        yield 'query with OneToMany join, order by ToOne expression' => [
            static function (self $testCase, EntityManagerInterface $em): Query {
                $query = $em->createQuery('SELECT u FROM ' . CmsUser::class . ' u JOIN u.articles a ORDER BY CONCAT(\'Test: \', u.id)');
                $query->setMaxResults(10);

                return $query;
            },
            self::RESULT_QUERY_WITH_SUBQUERY_TREE_WALKER,
            self::COUNT_QUERY_FAST,
            self::COUNT_QUERY_FAST_WITH_DISTINCT,
            self::COUNT_QUERY_FAST_SHOULD_DROP_GROUP_BY_FALSE,
            9,
        ];

        yield 'query with OneToMany join, order by ToMany expression' => [
            static function (self $testCase, EntityManagerInterface $em): Query {
                $query = $em->createQuery('SELECT u FROM ' . CmsUser::class . ' u JOIN u.articles a ORDER BY CONCAT(\'Test: \', a.id)');
                $query->setMaxResults(10);

                return $query;
            },
            self::RESULT_QUERY_WITH_SUBQUERY_OUTPUT_WALKER,
            self::COUNT_QUERY_FAST,
            self::COUNT_QUERY_FAST_WITH_DISTINCT,
            self::COUNT_QUERY_FAST_SHOULD_DROP_GROUP_BY_FALSE,
            9,
        ];
    }

    private function populate(): void
    {
        /** @var CmsUser[] $users */
        $users = [];
        for ($i = 0; $i < 9; $i++) {
            $user           = new CmsUser();
            $user->name     = 'User' . $i;
            $user->username = 'user' . $i;
            $user->status   = $i % 2 ? 'active' : 'inactive';
            $this->_em->persist($user);

            // Create "$i + 1" articles for each user.
            for ($j = 0; $j < $i + 1; $j++) {
                $article        = new CmsArticle();
                $article->topic = 'topic' . $i . $j;
                $article->text  = 'text' . $i . $j;
                $article->setAuthor($user);
                $article->version = 0;

                // Add an associated detail.
                if ($j === 0) {
                    $articleAssociatedDetail          = new CmsArticleAssociatedDetail();
                    $articleAssociatedDetail->article = $article;
                    $articleAssociatedDetail->detail  = 'big detail ' . $i . ',' . $j;
                    $this->_em->persist($articleAssociatedDetail);
                }

                // Add some comments to the first article of each user beyond user #3.
                if ($j === 0 && $i > 3) {
                    $comment        = new CmsComment();
                    $comment->topic = 'topic' . $i . $j;
                    $comment->text  = 'text' . $i . $j;
                    $comment->setAuthor($users[$i % 3]);
                    $comment->setArticle($article);
                    $this->_em->persist($comment);

                    $comment        = new CmsComment();
                    $comment->topic = 'topic' . $i . $j;
                    $comment->text  = 'text, reply' . $i . $j;
                    $comment->setAuthor($users[($i + 1) % 3]);
                    $comment->setArticle($article);
                    $this->_em->persist($comment);
                }

                $this->_em->persist($article);
            }

            $address          = new CmsAddress();
            $address->country = 'Country';
            $address->zip     = 'CNTR';
            $address->city    = 'City' . $i;
            $address->setUser($user);
            $this->_em->persist($address);

            $users[] = $user;
        }

        // Composite identifier test records
        for ($i = 0; $i < 5; $i++) {
            $customer              = new CustomerClass();
            $customer->companyCode = $i % 2 ? 'ACME USA' : 'ACME LATAM';
            $customer->code        = 'user' . $i;
            $customer->name        = 'User ' . $i;
            $this->_em->persist($customer);

            // Create "$i + 1" invoices for each customer.
            for ($j = 0; $j < $i + 1; $j++) {
                $invoice                = new InvoiceClass();
                $invoice->companyCode   = $customer->companyCode;
                $invoice->customerCode  = $customer->code;
                $invoice->invoiceNumber = 'invoice' . $i . '|' . $j;
                $invoice->customer      = $customer;

                $this->_em->persist($invoice);
            }
        }

        $this->_em->flush();
        $this->_em->clear();
    }
}
