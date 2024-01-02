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
use PHPUnit\Framework\MockObject\MockObject;

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
        $this->connection->expects(self::exactly(1))->method('executeQuery');
        $this->expectException(Query\QueryException::class);
        $this->expectExceptionMessage('Too many parameters: the query defines 1 parameters and you bound 2');

        $this->createPaginatorWithExtraParametersWithoutOutputWalkers([[10]])->getIterator();
    }

    /** @param int[][] $willReturnRows */
    private function createPaginatorWithExtraParametersWithoutOutputWalkers(array $willReturnRows): Paginator
    {
        $this->hydrator->method('hydrateAll')->willReturn($willReturnRows);
        $this->connection->method('executeQuery')->with(self::anything(), []);

        $query = new Query($this->em);
        $query->setDQL('SELECT u FROM Doctrine\\Tests\\Models\\CMS\\CmsUser u');
        $query->setParameters(['paramInWhere' => 1]);
        $query->setMaxResults(1);

        return (new Paginator($query, true))->setUseOutputWalkers(false);
    }
}
