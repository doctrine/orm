<?php

declare(strict_types=1);

namespace Doctrine\Tests\ORM\Tools\Pagination;

use Doctrine\DBAL\Platforms\AbstractPlatform;
use Doctrine\DBAL\Platforms\MySQLPlatform;
use Doctrine\DBAL\Platforms\OraclePlatform;
use Doctrine\DBAL\Platforms\PostgreSQLPlatform;
use Doctrine\ORM\Query;
use Doctrine\ORM\Tools\Pagination\LimitSubqueryOutputWalker;

use function class_exists;

// DBAL 2 compatibility
class_exists('Doctrine\DBAL\Platforms\MySqlPlatform');
class_exists('Doctrine\DBAL\Platforms\PostgreSqlPlatform');

final class LimitSubqueryOutputWalkerTest extends PaginationTestCase
{
    /**
     * @var AbstractPlatform|null
     */
    private $originalDatabasePlatform;

    protected function setUp(): void
    {
        parent::setUp();

        $this->originalDatabasePlatform = $this->entityManager->getConnection()->getDatabasePlatform();
    }

    protected function tearDown(): void
    {
        if ($this->originalDatabasePlatform) {
            $this->entityManager->getConnection()->setDatabasePlatform($this->originalDatabasePlatform);
        }

        parent::tearDown();
    }

    private function replaceDatabasePlatform(AbstractPlatform $platform): void
    {
        $this->entityManager->getConnection()->setDatabasePlatform($platform);
    }

    public function testLimitSubquery(): void
    {
        $query = $this->createQuery('SELECT p, c, a FROM Doctrine\Tests\ORM\Tools\Pagination\MyBlogPost p JOIN p.category c JOIN p.author a');

        self::assertSame(
            'SELECT DISTINCT id_0 FROM (SELECT m0_.id AS id_0, m0_.title AS title_1, c1_.id AS id_2, a2_.id AS id_3, a2_.name AS name_4, m0_.author_id AS author_id_5, m0_.category_id AS category_id_6 FROM MyBlogPost m0_ INNER JOIN Category c1_ ON m0_.category_id = c1_.id INNER JOIN Author a2_ ON m0_.author_id = a2_.id) dctrn_result LIMIT 20 OFFSET 10',
            $query->getSQL()
        );
    }

    public function testLimitSubqueryWithSortPg(): void
    {
        $this->replaceDatabasePlatform(new PostgreSQLPlatform());

        $query = $this->createQuery('SELECT p, c, a FROM Doctrine\Tests\ORM\Tools\Pagination\MyBlogPost p JOIN p.category c JOIN p.author a ORDER BY p.title');

        self::assertSame(
            'SELECT DISTINCT id_0, MIN(sclr_5) AS dctrn_minrownum FROM (SELECT m0_.id AS id_0, m0_.title AS title_1, c1_.id AS id_2, a2_.id AS id_3, a2_.name AS name_4, ROW_NUMBER() OVER(ORDER BY m0_.title ASC) AS sclr_5, m0_.author_id AS author_id_6, m0_.category_id AS category_id_7 FROM MyBlogPost m0_ INNER JOIN Category c1_ ON m0_.category_id = c1_.id INNER JOIN Author a2_ ON m0_.author_id = a2_.id) dctrn_result GROUP BY id_0 ORDER BY dctrn_minrownum ASC LIMIT 20 OFFSET 10',
            $query->getSQL()
        );
    }

    public function testLimitSubqueryWithScalarSortPg(): void
    {
        $this->replaceDatabasePlatform(new PostgreSQLPlatform());

        $query = $this->createQuery('SELECT u, g, COUNT(g.id) AS g_quantity FROM Doctrine\Tests\ORM\Tools\Pagination\User u JOIN u.groups g ORDER BY g_quantity');

        self::assertSame(
            'SELECT DISTINCT id_1, MIN(sclr_3) AS dctrn_minrownum FROM (SELECT COUNT(g0_.id) AS sclr_0, u1_.id AS id_1, g0_.id AS id_2, ROW_NUMBER() OVER(ORDER BY COUNT(g0_.id) ASC) AS sclr_3 FROM User u1_ INNER JOIN user_group u2_ ON u1_.id = u2_.user_id INNER JOIN groups g0_ ON g0_.id = u2_.group_id) dctrn_result GROUP BY id_1 ORDER BY dctrn_minrownum ASC LIMIT 20 OFFSET 10',
            $query->getSQL()
        );
    }

    public function testLimitSubqueryWithMixedSortPg(): void
    {
        $this->replaceDatabasePlatform(new PostgreSQLPlatform());

        $query = $this->createQuery('SELECT u, g, COUNT(g.id) AS g_quantity FROM Doctrine\Tests\ORM\Tools\Pagination\User u JOIN u.groups g ORDER BY g_quantity, u.id DESC');

        self::assertSame(
            'SELECT DISTINCT id_1, MIN(sclr_3) AS dctrn_minrownum FROM (SELECT COUNT(g0_.id) AS sclr_0, u1_.id AS id_1, g0_.id AS id_2, ROW_NUMBER() OVER(ORDER BY COUNT(g0_.id) ASC, u1_.id DESC) AS sclr_3 FROM User u1_ INNER JOIN user_group u2_ ON u1_.id = u2_.user_id INNER JOIN groups g0_ ON g0_.id = u2_.group_id) dctrn_result GROUP BY id_1 ORDER BY dctrn_minrownum ASC LIMIT 20 OFFSET 10',
            $query->getSQL()
        );
    }

    public function testLimitSubqueryWithHiddenScalarSortPg(): void
    {
        $this->replaceDatabasePlatform(new PostgreSQLPlatform());

        $query = $this->createQuery('SELECT u, g, COUNT(g.id) AS hidden g_quantity FROM Doctrine\Tests\ORM\Tools\Pagination\User u JOIN u.groups g ORDER BY g_quantity, u.id DESC');

        self::assertSame(
            'SELECT DISTINCT id_1, MIN(sclr_3) AS dctrn_minrownum FROM (SELECT COUNT(g0_.id) AS sclr_0, u1_.id AS id_1, g0_.id AS id_2, ROW_NUMBER() OVER(ORDER BY COUNT(g0_.id) ASC, u1_.id DESC) AS sclr_3 FROM User u1_ INNER JOIN user_group u2_ ON u1_.id = u2_.user_id INNER JOIN groups g0_ ON g0_.id = u2_.group_id) dctrn_result GROUP BY id_1 ORDER BY dctrn_minrownum ASC LIMIT 20 OFFSET 10',
            $query->getSQL()
        );
    }

    public function testLimitSubqueryPg(): void
    {
        $this->replaceDatabasePlatform(new PostgreSQLPlatform());

        $this->testLimitSubquery();
    }

    public function testLimitSubqueryWithSortOracle(): void
    {
        $this->replaceDatabasePlatform(new OraclePlatform());

        $query = $this->createQuery('SELECT p, c, a FROM Doctrine\Tests\ORM\Tools\Pagination\MyBlogPost p JOIN p.category c JOIN p.author a ORDER BY p.title');

        self::assertSame(
            'SELECT * FROM (SELECT a.*, ROWNUM AS doctrine_rownum FROM (SELECT DISTINCT ID_0, MIN(SCLR_5) AS dctrn_minrownum FROM (SELECT m0_.id AS ID_0, m0_.title AS TITLE_1, c1_.id AS ID_2, a2_.id AS ID_3, a2_.name AS NAME_4, ROW_NUMBER() OVER(ORDER BY m0_.title ASC) AS SCLR_5, m0_.author_id AS AUTHOR_ID_6, m0_.category_id AS CATEGORY_ID_7 FROM MyBlogPost m0_ INNER JOIN Category c1_ ON m0_.category_id = c1_.id INNER JOIN Author a2_ ON m0_.author_id = a2_.id) dctrn_result GROUP BY ID_0 ORDER BY dctrn_minrownum ASC) a WHERE ROWNUM <= 30) WHERE doctrine_rownum >= 11',
            $query->getSQL()
        );
    }

    public function testLimitSubqueryWithScalarSortOracle(): void
    {
        $this->replaceDatabasePlatform(new OraclePlatform());

        $query = $this->createQuery('SELECT u, g, COUNT(g.id) AS g_quantity FROM Doctrine\Tests\ORM\Tools\Pagination\User u JOIN u.groups g ORDER BY g_quantity');

        self::assertSame(
            'SELECT * FROM (SELECT a.*, ROWNUM AS doctrine_rownum FROM (SELECT DISTINCT ID_1, MIN(SCLR_3) AS dctrn_minrownum FROM (SELECT COUNT(g0_.id) AS SCLR_0, u1_.id AS ID_1, g0_.id AS ID_2, ROW_NUMBER() OVER(ORDER BY COUNT(g0_.id) ASC) AS SCLR_3 FROM User u1_ INNER JOIN user_group u2_ ON u1_.id = u2_.user_id INNER JOIN groups g0_ ON g0_.id = u2_.group_id) dctrn_result GROUP BY ID_1 ORDER BY dctrn_minrownum ASC) a WHERE ROWNUM <= 30) WHERE doctrine_rownum >= 11',
            $query->getSQL()
        );
    }

    public function testLimitSubqueryWithMixedSortOracle(): void
    {
        $this->replaceDatabasePlatform(new OraclePlatform());

        $query = $this->createQuery('SELECT u, g, COUNT(g.id) AS g_quantity FROM Doctrine\Tests\ORM\Tools\Pagination\User u JOIN u.groups g ORDER BY g_quantity, u.id DESC');

        self::assertSame(
            'SELECT * FROM (SELECT a.*, ROWNUM AS doctrine_rownum FROM (SELECT DISTINCT ID_1, MIN(SCLR_3) AS dctrn_minrownum FROM (SELECT COUNT(g0_.id) AS SCLR_0, u1_.id AS ID_1, g0_.id AS ID_2, ROW_NUMBER() OVER(ORDER BY COUNT(g0_.id) ASC, u1_.id DESC) AS SCLR_3 FROM User u1_ INNER JOIN user_group u2_ ON u1_.id = u2_.user_id INNER JOIN groups g0_ ON g0_.id = u2_.group_id) dctrn_result GROUP BY ID_1 ORDER BY dctrn_minrownum ASC) a WHERE ROWNUM <= 30) WHERE doctrine_rownum >= 11',
            $query->getSQL()
        );
    }

    public function testLimitSubqueryOracle(): void
    {
        $this->replaceDatabasePlatform(new OraclePlatform());

        $query = $this->createQuery('SELECT p, c, a FROM Doctrine\Tests\ORM\Tools\Pagination\MyBlogPost p JOIN p.category c JOIN p.author a');

        self::assertSame(
            'SELECT * FROM (SELECT a.*, ROWNUM AS doctrine_rownum FROM (SELECT DISTINCT ID_0 FROM (SELECT m0_.id AS ID_0, m0_.title AS TITLE_1, c1_.id AS ID_2, a2_.id AS ID_3, a2_.name AS NAME_4, m0_.author_id AS AUTHOR_ID_5, m0_.category_id AS CATEGORY_ID_6 FROM MyBlogPost m0_ INNER JOIN Category c1_ ON m0_.category_id = c1_.id INNER JOIN Author a2_ ON m0_.author_id = a2_.id) dctrn_result) a WHERE ROWNUM <= 30) WHERE doctrine_rownum >= 11',
            $query->getSQL()
        );
    }

    public function testCountQueryMixedResultsWithName(): void
    {
        $query = $this->createQuery('SELECT a, sum(a.name) as foo FROM Doctrine\Tests\ORM\Tools\Pagination\Author a');

        self::assertSame(
            'SELECT DISTINCT id_0 FROM (SELECT a0_.id AS id_0, a0_.name AS name_1, sum(a0_.name) AS sclr_2 FROM Author a0_) dctrn_result LIMIT 20 OFFSET 10',
            $query->getSQL()
        );
    }

    /** @group DDC-3336 */
    public function testCountQueryWithArithmeticOrderByCondition(): void
    {
        $this->replaceDatabasePlatform(new MySQLPlatform());

        $query = $this->createQuery('SELECT a FROM Doctrine\Tests\ORM\Tools\Pagination\Author a ORDER BY (1 - 1000) * 1 DESC');

        self::assertSame(
            'SELECT DISTINCT id_0 FROM (SELECT DISTINCT id_0, (1 - 1000) * 1 FROM (SELECT a0_.id AS id_0, a0_.name AS name_1 FROM Author a0_) dctrn_result_inner ORDER BY (1 - 1000) * 1 DESC) dctrn_result LIMIT 20 OFFSET 10',
            $query->getSQL()
        );
    }

    public function testCountQueryWithComplexScalarOrderByItemWithoutJoin(): void
    {
        $this->replaceDatabasePlatform(new MySQLPlatform());

        $query = $this->createQuery('SELECT a FROM Doctrine\Tests\ORM\Tools\Pagination\Avatar a ORDER BY a.imageHeight * a.imageWidth DESC');

        self::assertSame(
            'SELECT DISTINCT id_0 FROM (SELECT DISTINCT id_0, imageHeight_2 * imageWidth_3 FROM (SELECT a0_.id AS id_0, a0_.image AS image_1, a0_.imageHeight AS imageHeight_2, a0_.imageWidth AS imageWidth_3, a0_.imageAltDesc AS imageAltDesc_4, a0_.user_id AS user_id_5 FROM Avatar a0_) dctrn_result_inner ORDER BY imageHeight_2 * imageWidth_3 DESC) dctrn_result LIMIT 20 OFFSET 10',
            $query->getSQL()
        );
    }

    public function testCountQueryWithComplexScalarOrderByItemJoinedWithoutPartial(): void
    {
        $this->replaceDatabasePlatform(new MySQLPlatform());

        $query = $this->createQuery('SELECT u FROM Doctrine\Tests\ORM\Tools\Pagination\User u JOIN u.avatar a ORDER BY a.imageHeight * a.imageWidth DESC');

        self::assertSame(
            'SELECT DISTINCT id_0 FROM (SELECT DISTINCT id_0, imageHeight_3 * imageWidth_4 FROM (SELECT u0_.id AS id_0, a1_.id AS id_1, a1_.image AS image_2, a1_.imageHeight AS imageHeight_3, a1_.imageWidth AS imageWidth_4, a1_.imageAltDesc AS imageAltDesc_5, a1_.user_id AS user_id_6 FROM User u0_ INNER JOIN Avatar a1_ ON u0_.id = a1_.user_id) dctrn_result_inner ORDER BY imageHeight_3 * imageWidth_4 DESC) dctrn_result LIMIT 20 OFFSET 10',
            $query->getSQL()
        );
    }

    public function testCountQueryWithComplexScalarOrderByItemJoinedWithPartial(): void
    {
        $this->replaceDatabasePlatform(new MySQLPlatform());

        $query = $this->createQuery('SELECT u, partial a.{id, imageAltDesc} FROM Doctrine\Tests\ORM\Tools\Pagination\User u JOIN u.avatar a ORDER BY a.imageHeight * a.imageWidth DESC');

        self::assertSame(
            'SELECT DISTINCT id_0 FROM (SELECT DISTINCT id_0, imageHeight_5 * imageWidth_6 FROM (SELECT u0_.id AS id_0, a1_.id AS id_1, a1_.imageAltDesc AS imageAltDesc_2, a1_.id AS id_3, a1_.image AS image_4, a1_.imageHeight AS imageHeight_5, a1_.imageWidth AS imageWidth_6, a1_.imageAltDesc AS imageAltDesc_7, a1_.user_id AS user_id_8 FROM User u0_ INNER JOIN Avatar a1_ ON u0_.id = a1_.user_id) dctrn_result_inner ORDER BY imageHeight_5 * imageWidth_6 DESC) dctrn_result LIMIT 20 OFFSET 10',
            $query->getSQL()
        );
    }

    public function testCountQueryWithComplexScalarOrderByItemOracle(): void
    {
        $this->replaceDatabasePlatform(new OraclePlatform());

        $query = $this->createQuery('SELECT a FROM Doctrine\Tests\ORM\Tools\Pagination\Avatar a ORDER BY a.imageHeight * a.imageWidth DESC');

        self::assertSame(
            'SELECT * FROM (SELECT a.*, ROWNUM AS doctrine_rownum FROM (SELECT DISTINCT ID_0, MIN(SCLR_5) AS dctrn_minrownum FROM (SELECT a0_.id AS ID_0, a0_.image AS IMAGE_1, a0_.imageHeight AS IMAGEHEIGHT_2, a0_.imageWidth AS IMAGEWIDTH_3, a0_.imageAltDesc AS IMAGEALTDESC_4, ROW_NUMBER() OVER(ORDER BY a0_.imageHeight * a0_.imageWidth DESC) AS SCLR_5, a0_.user_id AS USER_ID_6 FROM Avatar a0_) dctrn_result GROUP BY ID_0 ORDER BY dctrn_minrownum ASC) a WHERE ROWNUM <= 30) WHERE doctrine_rownum >= 11',
            $query->getSQL()
        );
    }

    /** @group DDC-3434 */
    public function testLimitSubqueryWithHiddenSelectionInOrderBy(): void
    {
        $query = $this->createQuery(
            'SELECT a, a.name AS HIDDEN ord FROM Doctrine\Tests\ORM\Tools\Pagination\Author a ORDER BY ord DESC'
        );

        self::assertSame(
            'SELECT DISTINCT id_0 FROM (SELECT DISTINCT id_0, name_2 FROM (SELECT a0_.id AS id_0, a0_.name AS name_1, a0_.name AS name_2 FROM Author a0_) dctrn_result_inner ORDER BY name_2 DESC) dctrn_result LIMIT 20 OFFSET 10',
            $query->getSQL()
        );
    }

    public function testLimitSubqueryWithColumnWithSortDirectionInName(): void
    {
        $this->replaceDatabasePlatform(new MySQLPlatform());
        $query = $this->createQuery('SELECT a FROM Doctrine\Tests\ORM\Tools\Pagination\Avatar a ORDER BY a.imageAltDesc DESC');

        self::assertSame(
            'SELECT DISTINCT id_0 FROM (SELECT DISTINCT id_0, imageAltDesc_4 FROM (SELECT a0_.id AS id_0, a0_.image AS image_1, a0_.imageHeight AS imageHeight_2, a0_.imageWidth AS imageWidth_3, a0_.imageAltDesc AS imageAltDesc_4, a0_.user_id AS user_id_5 FROM Avatar a0_) dctrn_result_inner ORDER BY imageAltDesc_4 DESC) dctrn_result LIMIT 20 OFFSET 10',
            $query->getSQL()
        );
    }

    public function testLimitSubqueryWithOrderByInnerJoined(): void
    {
        $query = $this->createQuery('SELECT b FROM Doctrine\Tests\ORM\Tools\Pagination\BlogPost b JOIN b.author a ORDER BY a.name ASC');

        self::assertSame(
            'SELECT DISTINCT id_0 FROM (SELECT DISTINCT id_0, name_2 FROM (SELECT b0_.id AS id_0, a1_.id AS id_1, a1_.name AS name_2, b0_.author_id AS author_id_3, b0_.category_id AS category_id_4 FROM BlogPost b0_ INNER JOIN Author a1_ ON b0_.author_id = a1_.id) dctrn_result_inner ORDER BY name_2 ASC) dctrn_result LIMIT 20 OFFSET 10',
            $query->getSQL()
        );
    }

    public function testLimitSubqueryWithOrderByAndSubSelectInWhereClauseMySql(): void
    {
        $this->replaceDatabasePlatform(new MySQLPlatform());

        $query = $this->createQuery(
            'SELECT b FROM Doctrine\Tests\ORM\Tools\Pagination\BlogPost b
WHERE  ((SELECT COUNT(simple.id) FROM Doctrine\Tests\ORM\Tools\Pagination\BlogPost simple) = 1)
ORDER BY b.id DESC'
        );

        self::assertSame(
            'SELECT DISTINCT id_0 FROM (SELECT DISTINCT id_0 FROM (SELECT b0_.id AS id_0, b0_.author_id AS author_id_1, b0_.category_id AS category_id_2 FROM BlogPost b0_ WHERE ((SELECT COUNT(b1_.id) AS sclr_3 FROM BlogPost b1_) = 1)) dctrn_result_inner ORDER BY id_0 DESC) dctrn_result LIMIT 20 OFFSET 10',
            $query->getSQL()
        );
    }

    public function testLimitSubqueryWithOrderByAndSubSelectInWhereClausePgSql(): void
    {
        $this->replaceDatabasePlatform(new PostgreSQLPlatform());

        $query = $this->createQuery(
            'SELECT b FROM Doctrine\Tests\ORM\Tools\Pagination\BlogPost b
WHERE  ((SELECT COUNT(simple.id) FROM Doctrine\Tests\ORM\Tools\Pagination\BlogPost simple) = 1)
ORDER BY b.id DESC'
        );

        self::assertSame(
            'SELECT DISTINCT id_0, MIN(sclr_1) AS dctrn_minrownum FROM (SELECT b0_.id AS id_0, ROW_NUMBER() OVER(ORDER BY b0_.id DESC) AS sclr_1, b0_.author_id AS author_id_2, b0_.category_id AS category_id_3 FROM BlogPost b0_ WHERE ((SELECT COUNT(b1_.id) AS sclr_4 FROM BlogPost b1_) = 1)) dctrn_result GROUP BY id_0 ORDER BY dctrn_minrownum ASC LIMIT 20 OFFSET 10',
            $query->getSQL()
        );
    }

    /**
     * This tests ordering by property that has the 'declared' field.
     */
    public function testLimitSubqueryOrderByFieldFromMappedSuperclass(): void
    {
        $this->replaceDatabasePlatform(new MySQLPlatform());

        // now use the third one in query
        $query = $this->createQuery(
            'SELECT b FROM Doctrine\Tests\ORM\Tools\Pagination\Banner b ORDER BY b.id DESC'
        );

        self::assertSame(
            'SELECT DISTINCT id_0 FROM (SELECT DISTINCT id_0 FROM (SELECT b0_.id AS id_0, b0_.name AS name_1 FROM Banner b0_) dctrn_result_inner ORDER BY id_0 DESC) dctrn_result LIMIT 20 OFFSET 10',
            $query->getSQL()
        );
    }

    /**
     * Tests order by on a subselect expression (mysql).
     */
    public function testLimitSubqueryOrderBySubSelectOrderByExpression(): void
    {
        $this->replaceDatabasePlatform(new MySQLPlatform());

        $query = $this->createQuery(
            'SELECT a,
                (
                    SELECT MIN(bp.title)
                    FROM Doctrine\Tests\ORM\Tools\Pagination\MyBlogPost bp
                    WHERE bp.author = a
                ) AS HIDDEN first_blog_post
            FROM Doctrine\Tests\ORM\Tools\Pagination\Author a
            ORDER BY first_blog_post DESC'
        );

        self::assertSame(
            'SELECT DISTINCT id_0 FROM (SELECT DISTINCT id_0, sclr_2 FROM (SELECT a0_.id AS id_0, a0_.name AS name_1, (SELECT MIN(m1_.title) AS sclr_3 FROM MyBlogPost m1_ WHERE m1_.author_id = a0_.id) AS sclr_2 FROM Author a0_) dctrn_result_inner ORDER BY sclr_2 DESC) dctrn_result LIMIT 20 OFFSET 10',
            $query->getSQL()
        );
    }

    /**
     * Tests order by on a subselect expression invoking RowNumberOverFunction (postgres).
     */
    public function testLimitSubqueryOrderBySubSelectOrderByExpressionPg(): void
    {
        $this->replaceDatabasePlatform(new PostgreSQLPlatform());

        $query = $this->createQuery(
            'SELECT a,
                (
                    SELECT MIN(bp.title)
                    FROM Doctrine\Tests\ORM\Tools\Pagination\MyBlogPost bp
                    WHERE bp.author = a
                ) AS HIDDEN first_blog_post
            FROM Doctrine\Tests\ORM\Tools\Pagination\Author a
            ORDER BY first_blog_post DESC'
        );

        self::assertSame(
            'SELECT DISTINCT id_0, MIN(sclr_4) AS dctrn_minrownum FROM (SELECT a0_.id AS id_0, a0_.name AS name_1, (SELECT MIN(m1_.title) AS sclr_3 FROM MyBlogPost m1_ WHERE m1_.author_id = a0_.id) AS sclr_2, ROW_NUMBER() OVER(ORDER BY (SELECT MIN(m1_.title) AS sclr_5 FROM MyBlogPost m1_ WHERE m1_.author_id = a0_.id) DESC) AS sclr_4 FROM Author a0_) dctrn_result GROUP BY id_0 ORDER BY dctrn_minrownum ASC LIMIT 20 OFFSET 10',
            $query->getSQL()
        );
    }

    /**
     * Tests order by on a subselect expression invoking RowNumberOverFunction (oracle).
     */
    public function testLimitSubqueryOrderBySubSelectOrderByExpressionOracle(): void
    {
        $this->replaceDatabasePlatform(new OraclePlatform());

        $query = $this->createQuery(
            'SELECT a,
                (
                    SELECT MIN(bp.title)
                    FROM Doctrine\Tests\ORM\Tools\Pagination\MyBlogPost bp
                    WHERE bp.author = a
                ) AS HIDDEN first_blog_post
            FROM Doctrine\Tests\ORM\Tools\Pagination\Author a
            ORDER BY first_blog_post DESC'
        );

        self::assertSame(
            'SELECT * FROM (SELECT a.*, ROWNUM AS doctrine_rownum FROM (SELECT DISTINCT ID_0, MIN(SCLR_4) AS dctrn_minrownum FROM (SELECT a0_.id AS ID_0, a0_.name AS NAME_1, (SELECT MIN(m1_.title) AS SCLR_3 FROM MyBlogPost m1_ WHERE m1_.author_id = a0_.id) AS SCLR_2, ROW_NUMBER() OVER(ORDER BY (SELECT MIN(m1_.title) AS SCLR_5 FROM MyBlogPost m1_ WHERE m1_.author_id = a0_.id) DESC) AS SCLR_4 FROM Author a0_) dctrn_result GROUP BY ID_0 ORDER BY dctrn_minrownum ASC) a WHERE ROWNUM <= 30) WHERE doctrine_rownum >= 11',
            $query->getSQL()
        );
    }

    private function createQuery(string $dql): Query
    {
        $query = $this->entityManager->createQuery($dql);
        $query->setHint(Query::HINT_CUSTOM_OUTPUT_WALKER, LimitSubqueryOutputWalker::class);
        $query->setFirstResult(10);
        $query->setMaxResults(20);

        return $query;
    }
}
