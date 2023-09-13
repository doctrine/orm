<?php

declare(strict_types=1);

namespace Doctrine\Tests\ORM\Tools\Pagination;

use Doctrine\DBAL\Connection;
use Doctrine\DBAL\Driver;
use Doctrine\DBAL\Result;
use Doctrine\ORM\Decorator\EntityManagerDecorator;
use Doctrine\ORM\EntityManagerInterface;
use Doctrine\ORM\Internal\Hydration\AbstractHydrator;
use Doctrine\ORM\Query;
use Doctrine\ORM\Tools\Pagination\Paginator;
use Doctrine\Tests\Mocks\ConnectionMock;
use Doctrine\Tests\OrmTestCase;
use Doctrine\Tests\PHPUnitCompatibility\MockBuilderCompatibilityTools;
use Generator;
use PHPUnit\Framework\MockObject\MockObject;

use function call_user_func;
use function preg_replace;

class PaginatorTest extends OrmTestCase
{
    use MockBuilderCompatibilityTools;

    /** @var Connection&MockObject */
    private $connection;
    /** @var EntityManagerInterface&MockObject */
    private $em;
    /** @var AbstractHydrator&MockObject */
    private $hydrator;

    protected function setUp(): void
    {
        $this->connection = $this->getMockBuilderWithOnlyMethods(ConnectionMock::class, ['executeQuery'])
            ->setConstructorArgs([[], $this->createMock(Driver::class)])
            ->getMock();

        $this->em = $this->getMockBuilderWithOnlyMethods(EntityManagerDecorator::class, ['newHydrator'])
            ->setConstructorArgs([$this->getTestEntityManager($this->connection)])
            ->getMock();

        $this->hydrator = $this->createMock(AbstractHydrator::class);
        $this->em->method('newHydrator')->willReturn($this->hydrator);
    }

    public function testExtraParametersAreStrippedWhenWalkerRemovingOriginalSelectElementsIsUsed(): void
    {
        $paramInWhere     = 1;
        $paramInSubSelect = 2;
        $returnedIds      = [10];

        $this->hydrator->method('hydrateAll')->willReturn([$returnedIds]);

        $query = new Query($this->em);
        $query->setDQL(
            'SELECT u,
                (
                    SELECT MAX(a.version)
                    FROM Doctrine\\Tests\\Models\\CMS\\CmsArticle a
                    WHERE a.user = u AND 1 = :paramInSubSelect
                ) AS HIDDEN max_version
            FROM Doctrine\\Tests\\Models\\CMS\\CmsUser u
            WHERE u.id = :paramInWhere'
        );
        $query->setParameters(['paramInWhere' => $paramInWhere, 'paramInSubSelect' => $paramInSubSelect]);
        $query->setMaxResults(1);
        $paginator = (new Paginator($query, true))->setUseOutputWalkers(false);

        $receivedParams = [];
        $resultMock     = $this->createMock(Result::class);
        $this->connection
            ->method('executeQuery')
            ->willReturnCallback(static function (string $sql, array $params) use (&$receivedParams, $resultMock): Result {
                $receivedParams[] = $params;

                return $resultMock;
            });

        $paginator->count();
        $paginator->getIterator();

        self::assertSame([
            [$paramInWhere],
            [$paramInWhere],
            [$paramInSubSelect, $paramInWhere, $returnedIds],
        ], $receivedParams);
    }

    public function testPaginatorNotCaringAboutExtraParametersWithoutOutputWalkers(): void
    {
        $this->connection->expects(self::exactly(3))->method('executeQuery');

        $this->createPaginatorWithExtraParametersWithoutOutputWalkers([])->count();
        $this->createPaginatorWithExtraParametersWithoutOutputWalkers([[10]])->count();
        $this->createPaginatorWithExtraParametersWithoutOutputWalkers([])->getIterator();
    }

    public function testgetIteratorDoesCareAboutExtraParametersWithoutOutputWalkersWhenResultIsNotEmpty(): void
    {
        $this->connection->expects(self::exactly(2))->method('executeQuery');

        $this->createPaginatorWithExtraParametersWithoutOutputWalkers([[10]], null)->getIterator();
    }

    /** @param int[][] $willReturnRows */
    private function createPaginatorWithExtraParametersWithoutOutputWalkers(array $willReturnRows, ?array $results = []): Paginator
    {
        $this->hydrator->method('hydrateAll')->willReturn($willReturnRows);
        if ($results !== null) {
            $this->connection->method('executeQuery')->with(self::anything(), []);
        }

        $query = new Query($this->em);
        $query->setDQL('SELECT u FROM Doctrine\\Tests\\Models\\CMS\\CmsUser u');
        $query->setParameters(['paramInWhere' => 1]);
        $query->setMaxResults(1);

        return (new Paginator($query, true))->setUseOutputWalkers(false);
    }

    /** @dataProvider dataRedunandQueryPartsAreRemovedForWhereInWalker */
    public function testRedunandQueryPartsAreRemovedForWhereInWalker(
        string $dql,
        array $params,
        array $expectedQueries
    ): void {
        $this->hydrator->method('hydrateAll')->willReturn([[10]]);

        $query = new Query($this->em);
        $query->setDQL($dql);
        $query->setParameters($params);

        $query->setMaxResults(1);
        $paginator = (new Paginator($query, true))->setUseOutputWalkers(false);

        $queryIndex   = 0;
        $resultMock   = $this->createMock(Result::class);
        $this->connection
            ->expects($this->exactly(2))
            ->method('executeQuery')
            ->willReturnCallback(static function (string $actualSql) use ($expectedQueries, $resultMock, &$queryIndex): Result {
                $expectedSql = preg_replace('!\s+!', ' ', $expectedQueries[$queryIndex]);
                self::assertEquals($expectedSql, $actualSql);

                $queryIndex++;

                return $resultMock;
            });

        $paginator->getIterator();
    }

    public static function dataRedunandQueryPartsAreRemovedForWhereInWalker(): Generator
    {
        yield 'join that is used in where only' => [
            'SELECT u
            FROM Doctrine\\Tests\\Models\\CMS\\CmsUser u
            JOIN Doctrine\\Tests\\Models\\CMS\\CmsAddress a
            WHERE a.city = :filterCity',
            ['filterCity' => 'London'],
            [
                'SELECT DISTINCT c0_.id AS id_0 FROM cms_users c0_ INNER JOIN cms_addresses c1_ WHERE c1_.city = ? LIMIT 1',
                'SELECT c0_.id AS id_0, c0_.status AS status_1, c0_.username AS username_2, c0_.name AS name_3, c0_.email_id AS email_id_4
                FROM cms_users c0_
                WHERE c0_.id IN (?)',
            ],
        ];

        yield 'join that is used in select and where' => [
            'SELECT u, a
            FROM Doctrine\\Tests\\Models\\CMS\\CmsUser u
            JOIN Doctrine\\Tests\\Models\\CMS\\CmsAddress a
            WHERE a.city = :filterCity',
            ['filterCity' => 'London'],
            [
                'SELECT DISTINCT c0_.id AS id_0 FROM cms_users c0_ INNER JOIN cms_addresses c1_ WHERE c1_.city = ? LIMIT 1',
                'SELECT
                    c0_.id AS id_0, c0_.status AS status_1, c0_.username AS username_2, c0_.name AS name_3, c1_.id AS id_4,
                    c1_.country AS country_5, c1_.zip AS zip_6, c1_.city AS city_7, c0_.email_id AS email_id_8, c1_.user_id AS user_id_9
                FROM cms_users c0_
                INNER JOIN cms_addresses c1_
                WHERE c0_.id IN (?)',
            ],
        ];

        yield 'subselect with parameter' => [
            'SELECT u,
                (
                    SELECT MAX(article.version)
                    FROM Doctrine\\Tests\\Models\\CMS\\CmsArticle article
                    WHERE article.user = u AND 1 = :paramInSubSelect
                ) AS HIDDEN max_version
            FROM Doctrine\\Tests\\Models\\CMS\\CmsUser u
            JOIN Doctrine\\Tests\\Models\\CMS\\CmsAddress a
            WHERE a.city = :filterCity',
            ['filterCity' => 'London', 'paramInSubSelect' => 1],
            [
                'SELECT DISTINCT c0_.id AS id_0 FROM cms_users c0_ INNER JOIN cms_addresses c1_ WHERE c1_.city = ? LIMIT 1',
                'SELECT
                    c0_.id AS id_0, c0_.status AS status_1, c0_.username AS username_2, c0_.name AS name_3,
                    (SELECT MAX(c1_.version) AS sclr_5
                        FROM cms_articles c1_
                        WHERE c1_.user_id = c0_.id AND 1 = ?)
                    AS sclr_4,
                    c0_.email_id AS email_id_6 FROM cms_users c0_
                INNER JOIN cms_addresses c2_
                WHERE c2_.city = ?
                AND c0_.id IN (?)',
            ],
        ];

        yield 'subselect without parameter' => [
            'SELECT u,
                (
                    SELECT MAX(article.version)
                    FROM Doctrine\\Tests\\Models\\CMS\\CmsArticle article
                    WHERE article.user = u
                ) AS HIDDEN max_version
            FROM Doctrine\\Tests\\Models\\CMS\\CmsUser u
            JOIN Doctrine\\Tests\\Models\\CMS\\CmsAddress a
            WHERE a.city = :filterCity',
            ['filterCity' => 'London'],
            [
                'SELECT DISTINCT c0_.id AS id_0 FROM cms_users c0_ INNER JOIN cms_addresses c1_ WHERE c1_.city = ? LIMIT 1',
                'SELECT
                    c0_.id AS id_0, c0_.status AS status_1, c0_.username AS username_2, c0_.name AS name_3,
                    (SELECT MAX(c1_.version) AS sclr_5
                        FROM cms_articles c1_
                        WHERE c1_.user_id = c0_.id)
                    AS sclr_4,
                    c0_.email_id AS email_id_6 FROM cms_users c0_
                INNER JOIN cms_addresses c2_
                WHERE c2_.city = ?
                AND c0_.id IN (?)',
            ],
        ];
    }
}
