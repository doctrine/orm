<?php

declare(strict_types=1);

namespace Doctrine\Tests\ORM\Tools\Pagination;

use Doctrine\DBAL\Connection;
use Doctrine\ORM\Decorator\EntityManagerDecorator;
use Doctrine\ORM\EntityManagerInterface;
use Doctrine\ORM\Internal\Hydration\AbstractHydrator;
use Doctrine\ORM\Query;
use Doctrine\ORM\Tools\Pagination\Paginator;
use Doctrine\Tests\Mocks\ConnectionMock;
use Doctrine\Tests\Mocks\DriverMock;
use Doctrine\Tests\OrmTestCase;

class PaginatorTest extends OrmTestCase
{
    /** @var Connection */
    private $connection;
    /** @var EntityManagerInterface */
    private $em;
    /** @var AbstractHydrator */
    private $hydrator;

    protected function setUp() : void
    {
        $this->connection = $this->getMockBuilder(ConnectionMock::class)
            ->setConstructorArgs([[], new DriverMock()])
            ->setMethods(['executeQuery'])
            ->getMock()
        ;

        $this->em = $this->getMockBuilder(EntityManagerDecorator::class)
            ->setConstructorArgs([$this->_getTestEntityManager($this->connection)])
            ->setMethods(['newHydrator'])
            ->getMock()
        ;

        $this->hydrator = $this->createMock(AbstractHydrator::class);
        $this->em->method('newHydrator')->willReturn($this->hydrator);
    }

    public function testExtraParametersAreStrippedWhenWalkerRemovingOriginalSelectElementsIsUsed() : void
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

        $this->connection->expects($this->exactly(3))->method('executeQuery');

        $this->connection->expects($this->at(0))
            ->method('executeQuery')
            ->with($this->anything(), [$paramInWhere])
        ;

        $this->connection->expects($this->at(1))
            ->method('executeQuery')
            ->with($this->anything(), [$paramInWhere])
        ;

        $this->connection->expects($this->at(2))
            ->method('executeQuery')
            ->with($this->anything(), [$paramInSubSelect, $paramInWhere, $returnedIds])
        ;

        $paginator->count();
        $paginator->getIterator();
    }

    public function testPaginatorNotCaringAboutExtraParametersWithoutOutputWalkers() : void
    {
        $this->connection->expects($this->exactly(3))->method('executeQuery');

        $this->createPaginatorWithExtraParametersWithoutOutputWalkers([])->count();
        $this->createPaginatorWithExtraParametersWithoutOutputWalkers([[10]])->count();
        $this->createPaginatorWithExtraParametersWithoutOutputWalkers([])->getIterator();
    }

    public function testgetIteratorDoesCareAboutExtraParametersWithoutOutputWalkersWhenResultIsNotEmpty() : void
    {
        $this->connection->expects($this->exactly(1))->method('executeQuery');
        $this->expectException(Query\QueryException::class);
        $this->expectExceptionMessage('Too many parameters: the query defines 1 parameters and you bound 2');

        $this->createPaginatorWithExtraParametersWithoutOutputWalkers([[10]])->getIterator();
    }

    /**
     * @param int[][] $willReturnRows
     */
    private function createPaginatorWithExtraParametersWithoutOutputWalkers(array $willReturnRows) : Paginator
    {
        $this->hydrator->method('hydrateAll')->willReturn($willReturnRows);
        $this->connection->method('executeQuery')->with($this->anything(), []);

        $query = new Query($this->em);
        $query->setDQL('SELECT u FROM Doctrine\\Tests\\Models\\CMS\\CmsUser u');
        $query->setParameters(['paramInWhere' => 1]);
        $query->setMaxResults(1);

        return (new Paginator($query, true))->setUseOutputWalkers(false);
    }
}
