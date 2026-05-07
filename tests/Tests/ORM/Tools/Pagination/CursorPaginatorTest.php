<?php

declare(strict_types=1);

namespace Doctrine\Tests\ORM\Tools\Pagination;

use Doctrine\DBAL\Connection;
use Doctrine\DBAL\Driver;
use Doctrine\DBAL\Platforms\AbstractPlatform;
use Doctrine\DBAL\Result;
use Doctrine\DBAL\Schema\Name\UnquotedIdentifierFolding;
use Doctrine\ORM\Decorator\EntityManagerDecorator;
use Doctrine\ORM\EntityManagerInterface;
use Doctrine\ORM\Internal\Hydration\AbstractHydrator;
use Doctrine\ORM\Query;
use Doctrine\ORM\Tools\Pagination\Cursor;
use Doctrine\ORM\Tools\Pagination\CursorItem;
use Doctrine\ORM\Tools\Pagination\CursorPaginator;
use Doctrine\Tests\OrmTestCase;
use LogicException;
use PHPUnit\Framework\MockObject\MockObject;
use SortDirection;

use function enum_exists;

class CursorPaginatorTest extends OrmTestCase
{
    private Connection&MockObject $connection;
    private EntityManagerInterface&MockObject $em;
    private AbstractHydrator&MockObject $hydrator;

    protected function setUp(): void
    {
        $platform = $this->getMockBuilder(AbstractPlatform::class)
            ->setConstructorArgs(enum_exists(UnquotedIdentifierFolding::class) ? [UnquotedIdentifierFolding::UPPER] : [])
            ->getMock();
        $platform->method('supportsIdentityColumns')
            ->willReturn(true);

        $driver = $this->createMock(Driver::class);
        $driver->method('getDatabasePlatform')
            ->willReturn($platform);

        $this->connection = $this->getMockBuilder(Connection::class)
            ->onlyMethods(['executeQuery'])
            ->setConstructorArgs([[], $driver])
            ->getMock();

        $this->em = $this->getMockBuilder(EntityManagerDecorator::class)
            ->onlyMethods(['newHydrator'])
            ->setConstructorArgs([$this->createTestEntityManagerWithConnection($this->connection)])
            ->getMock();

        $this->hydrator = $this->createMock(AbstractHydrator::class);
        $this->em->method('newHydrator')->willReturn($this->hydrator);
    }

    public function testPaginatorAcceptsQueryBuilder(): void
    {
        $qb = $this->em->createQueryBuilder()
            ->select('p')
            ->from(BlogPost::class, 'p')
            ->orderBy('p.id', SortDirection::Ascending);

        $paginator = new CursorPaginator($qb);

        self::assertInstanceOf(Query::class, $paginator->getQuery());
    }

    public function testHasNextPageWhenMoreResultsExist(): void
    {
        $items = [
            (object) ['id' => 1],
            (object) ['id' => 2],
            (object) ['id' => 3],
            (object) ['id' => 4],
        ];
        $this->hydrator->method('hydrateAll')->willReturn($items);
        $result = $this->createMock(Result::class);
        $this->connection->method('executeQuery')->willReturn($result);

        $query = new Query($this->em);
        $query->setDQL('SELECT p FROM Doctrine\Tests\ORM\Tools\Pagination\BlogPost p ORDER BY p.id ASC');

        $paginator = new CursorPaginator($query, queryProducesDuplicates: false);
        $paginator->paginate(null, 3);

        self::assertTrue($paginator->hasNextPage());
        self::assertFalse($paginator->hasPreviousPage());
        self::assertSame(3, $paginator->countPageItems());
    }

    public function testHasNoPagesOnFirstPageWithoutMoreResults(): void
    {
        $items = [(object) ['id' => 1]];
        $this->hydrator->method('hydrateAll')->willReturn($items);
        $result = $this->createMock(Result::class);
        $this->connection->method('executeQuery')->willReturn($result);

        $query = new Query($this->em);
        $query->setDQL('SELECT p FROM Doctrine\Tests\ORM\Tools\Pagination\BlogPost p ORDER BY p.id ASC');

        $paginator = new CursorPaginator($query, queryProducesDuplicates: false);
        $paginator->paginate(null, 10);

        self::assertFalse($paginator->hasPreviousPage());
        self::assertFalse($paginator->hasNextPage());
        self::assertFalse($paginator->hasToPaginate());
    }

    public function testGetNextCursorAsStringThrowsWhenNoNextPage(): void
    {
        $items = [(object) ['id' => 1]];
        $this->hydrator->method('hydrateAll')->willReturn($items);
        $result = $this->createMock(Result::class);
        $this->connection->method('executeQuery')->willReturn($result);

        $query = new Query($this->em);
        $query->setDQL('SELECT p FROM Doctrine\Tests\ORM\Tools\Pagination\BlogPost p ORDER BY p.id ASC');

        $paginator = new CursorPaginator($query, queryProducesDuplicates: false);
        $paginator->paginate(null, 10);

        $this->expectException(LogicException::class);
        $paginator->getNextCursorAsString();
    }

    public function testGetPreviousCursorAsStringThrowsWhenNoPreviousPage(): void
    {
        $items = [(object) ['id' => 1]];
        $this->hydrator->method('hydrateAll')->willReturn($items);
        $result = $this->createMock(Result::class);
        $this->connection->method('executeQuery')->willReturn($result);

        $query = new Query($this->em);
        $query->setDQL('SELECT p FROM Doctrine\Tests\ORM\Tools\Pagination\BlogPost p ORDER BY p.id ASC');

        $paginator = new CursorPaginator($query, queryProducesDuplicates: false);
        $paginator->paginate(null, 10);

        $this->expectException(LogicException::class);
        $paginator->getPreviousCursorAsString();
    }

    public function testGetNextCursorThrowsWhenNoNextPage(): void
    {
        $items = [(object) ['id' => 1]];
        $this->hydrator->method('hydrateAll')->willReturn($items);
        $result = $this->createMock(Result::class);
        $this->connection->method('executeQuery')->willReturn($result);

        $query = new Query($this->em);
        $query->setDQL('SELECT p FROM Doctrine\Tests\ORM\Tools\Pagination\BlogPost p ORDER BY p.id ASC');

        $paginator = new CursorPaginator($query, queryProducesDuplicates: false);
        $paginator->paginate(null, 10);

        $this->expectException(LogicException::class);
        $paginator->getNextCursor();
    }

    public function testGetPreviousCursorThrowsWhenNoPreviousPage(): void
    {
        $items = [(object) ['id' => 1]];
        $this->hydrator->method('hydrateAll')->willReturn($items);
        $result = $this->createMock(Result::class);
        $this->connection->method('executeQuery')->willReturn($result);

        $query = new Query($this->em);
        $query->setDQL('SELECT p FROM Doctrine\Tests\ORM\Tools\Pagination\BlogPost p ORDER BY p.id ASC');

        $paginator = new CursorPaginator($query, queryProducesDuplicates: false);
        $paginator->paginate(null, 10);

        $this->expectException(LogicException::class);
        $paginator->getPreviousCursor();
    }

    public function testGetNextCursorWhenMoreResultsExist(): void
    {
        $items = [
            (object) ['id' => 1],
            (object) ['id' => 2],
        ];
        $this->hydrator->method('hydrateAll')->willReturn($items);
        $result = $this->createMock(Result::class);
        $this->connection->method('executeQuery')->willReturn($result);

        $query = new Query($this->em);
        $query->setDQL('SELECT p FROM Doctrine\Tests\ORM\Tools\Pagination\BlogPost p ORDER BY p.id ASC');

        $paginator = new CursorPaginator($query, queryProducesDuplicates: false);
        $paginator->paginate(null, 1);

        $nextCursor = $paginator->getNextCursor();

        self::assertTrue($nextCursor->isNext());
    }

    public function testGetNextCursorAsStringReturnsStringWhenNextPageExists(): void
    {
        $items = [
            (object) ['id' => 1],
            (object) ['id' => 2],
        ];
        $this->hydrator->method('hydrateAll')->willReturn($items);
        $result = $this->createMock(Result::class);
        $this->connection->method('executeQuery')->willReturn($result);

        $query = new Query($this->em);
        $query->setDQL('SELECT p FROM Doctrine\Tests\ORM\Tools\Pagination\BlogPost p ORDER BY p.id ASC');

        $paginator = new CursorPaginator($query, queryProducesDuplicates: false);
        $paginator->paginate(null, 1);

        self::assertNotEmpty($paginator->getNextCursorAsString());
    }

    public function testGetPreviousCursorAsStringReturnsStringWhenPreviousPageExists(): void
    {
        $items = [
            (object) ['id' => 1],
            (object) ['id' => 2],
        ];
        $this->hydrator->method('hydrateAll')->willReturn($items);
        $result = $this->createMock(Result::class);
        $this->connection->method('executeQuery')->willReturn($result);

        $query = new Query($this->em);
        $query->setDQL('SELECT p FROM Doctrine\Tests\ORM\Tools\Pagination\BlogPost p ORDER BY p.id ASC');

        $cursor    = (new Cursor(['p__id' => 1], true))->encodeToString();
        $paginator = new CursorPaginator($query, queryProducesDuplicates: false);
        $paginator->paginate($cursor, 10);

        self::assertNotEmpty($paginator->getPreviousCursorAsString());
    }

    public function testEmptyResultSet(): void
    {
        $this->hydrator->method('hydrateAll')->willReturn([]);
        $result = $this->createMock(Result::class);
        $this->connection->method('executeQuery')->willReturn($result);

        $query = new Query($this->em);
        $query->setDQL('SELECT p FROM Doctrine\Tests\ORM\Tools\Pagination\BlogPost p ORDER BY p.id ASC');

        $paginator = new CursorPaginator($query, queryProducesDuplicates: false);
        $paginator->paginate(null, 10);

        self::assertSame(0, $paginator->countPageItems());
        self::assertFalse($paginator->hasNextPage());
        self::assertFalse($paginator->hasPreviousPage());
    }

    public function testIteratorReturnsItems(): void
    {
        $items = [
            (object) ['id' => 1],
            (object) ['id' => 2],
        ];
        $this->hydrator->method('hydrateAll')->willReturn($items);
        $result = $this->createMock(Result::class);
        $this->connection->method('executeQuery')->willReturn($result);

        $query = new Query($this->em);
        $query->setDQL('SELECT p FROM Doctrine\Tests\ORM\Tools\Pagination\BlogPost p ORDER BY p.id ASC');

        $paginator = new CursorPaginator($query, queryProducesDuplicates: false);
        $paginator->paginate(null, 10);

        $iteratedItems = [];
        foreach ($paginator as $item) {
            $iteratedItems[] = $item;
        }

        self::assertCount(2, $iteratedItems);
    }

    public function testGetValuesReturnsRawEntities(): void
    {
        $items = [
            (object) ['id' => 1],
            (object) ['id' => 2],
        ];
        $this->hydrator->method('hydrateAll')->willReturn($items);
        $result = $this->createMock(Result::class);
        $this->connection->method('executeQuery')->willReturn($result);

        $query = new Query($this->em);
        $query->setDQL('SELECT p FROM Doctrine\Tests\ORM\Tools\Pagination\BlogPost p ORDER BY p.id ASC');

        $paginator = new CursorPaginator($query, queryProducesDuplicates: false);
        $paginator->paginate(null, 10);

        $values = $paginator->getValues();

        self::assertCount(2, $values);
        self::assertSame($items[0], $values[0]);
        self::assertSame($items[1], $values[1]);
    }

    public function testGetItemsReturnsCursorItems(): void
    {
        $items = [
            (object) ['id' => 1],
            (object) ['id' => 2],
        ];
        $this->hydrator->method('hydrateAll')->willReturn($items);
        $result = $this->createMock(Result::class);
        $this->connection->method('executeQuery')->willReturn($result);

        $query = new Query($this->em);
        $query->setDQL('SELECT p FROM Doctrine\Tests\ORM\Tools\Pagination\BlogPost p ORDER BY p.id ASC');

        $paginator = new CursorPaginator($query, queryProducesDuplicates: false);
        $paginator->paginate(null, 10);

        $cursorItems = $paginator->getItems();

        self::assertCount(2, $cursorItems);
        self::assertInstanceOf(CursorItem::class, $cursorItems[0]);
        self::assertSame($items[0], $cursorItems[0]->getValue());
        self::assertInstanceOf(Cursor::class, $cursorItems[0]->getCursor());
        self::assertSame($items[1], $cursorItems[1]->getValue());
    }

    public function testGetItemsReturnsEmptyArrayWhenNoResults(): void
    {
        $this->hydrator->method('hydrateAll')->willReturn([]);
        $result = $this->createMock(Result::class);
        $this->connection->method('executeQuery')->willReturn($result);

        $query = new Query($this->em);
        $query->setDQL('SELECT p FROM Doctrine\Tests\ORM\Tools\Pagination\BlogPost p ORDER BY p.id ASC');

        $paginator = new CursorPaginator($query, queryProducesDuplicates: false);
        $paginator->paginate(null, 10);

        self::assertSame([], $paginator->getItems());
        self::assertSame([], $paginator->getValues());
    }

    public function testPaginateWithCursorInstance(): void
    {
        $items = [
            (object) ['id' => 1],
            (object) ['id' => 2],
        ];
        $this->hydrator->method('hydrateAll')->willReturn($items);
        $result = $this->createMock(Result::class);
        $this->connection->method('executeQuery')->willReturn($result);

        $query = new Query($this->em);
        $query->setDQL('SELECT p FROM Doctrine\Tests\ORM\Tools\Pagination\BlogPost p ORDER BY p.id ASC');

        $cursor    = new Cursor(['p.id' => 1], true);
        $paginator = new CursorPaginator($query, queryProducesDuplicates: false);
        $paginator->paginate($cursor, 10);

        self::assertNotEmpty($paginator->getPreviousCursorAsString());
    }

    public function testPaginateWithEmptyCursorTreatedAsFirstPage(): void
    {
        $items = [(object) ['id' => 1]];
        $this->hydrator->method('hydrateAll')->willReturn($items);
        $result = $this->createMock(Result::class);
        $this->connection->method('executeQuery')->willReturn($result);

        $query = new Query($this->em);
        $query->setDQL('SELECT p FROM Doctrine\Tests\ORM\Tools\Pagination\BlogPost p ORDER BY p.id ASC');

        $paginator = new CursorPaginator($query, queryProducesDuplicates: false);
        $paginator->paginate('', 10);

        self::assertFalse($paginator->hasPreviousPage());
        self::assertFalse($paginator->hasNextPage());
    }

    public function testPaginateWithPreviousCursorReversesItems(): void
    {
        $items = [
            (object) ['id' => 3],
            (object) ['id' => 2],
            (object) ['id' => 1],
        ];
        $this->hydrator->method('hydrateAll')->willReturn($items);
        $result = $this->createMock(Result::class);
        $this->connection->method('executeQuery')->willReturn($result);

        $query = new Query($this->em);
        $query->setDQL('SELECT p FROM Doctrine\Tests\ORM\Tools\Pagination\BlogPost p ORDER BY p.id ASC');

        $previousCursor = (new Cursor(['p__id' => 3], false))->encodeToString();

        $paginator = new CursorPaginator($query, queryProducesDuplicates: false);
        $paginator->paginate($previousCursor, 10);

        $values = $paginator->getValues();
        self::assertCount(3, $values);
        self::assertSame(1, $values[0]->id);
        self::assertSame(2, $values[1]->id);
        self::assertSame(3, $values[2]->id);
    }

    public function testCountCurrentPageThrowsWhenPaginateNotCalled(): void
    {
        $query = new Query($this->em);
        $query->setDQL('SELECT p FROM Doctrine\Tests\ORM\Tools\Pagination\BlogPost p ORDER BY p.id ASC');

        $paginator = new CursorPaginator($query, queryProducesDuplicates: false);

        $this->expectException(LogicException::class);
        $paginator->countPageItems();
    }

    public function testDefaultQueryProducesDuplicatesIsTrue(): void
    {
        $query = new Query($this->em);
        $query->setDQL('SELECT p FROM Doctrine\Tests\ORM\Tools\Pagination\BlogPost p ORDER BY p.id ASC');

        $paginator = new CursorPaginator($query);

        self::assertTrue($paginator->getQueryProducesDuplicates());
    }

    public function testGetTotalCountReturnsCountQueryResult(): void
    {
        $this->hydrator->method('hydrateAll')->willReturn([[1 => 42]]);
        $result = $this->createMock(Result::class);
        $this->connection->method('executeQuery')->willReturn($result);

        $query = new Query($this->em);
        $query->setDQL('SELECT u FROM Doctrine\Tests\Models\CMS\CmsUser u');

        $paginator = (new CursorPaginator($query, queryProducesDuplicates: false))->setUseOutputWalkers(false);

        self::assertSame(42, $paginator->getTotalCount());
    }

    public function testGetTotalCountIsCached(): void
    {
        $this->hydrator->method('hydrateAll')->willReturn([[1 => 5]]);
        $result = $this->createMock(Result::class);
        $this->connection->expects(self::once())->method('executeQuery')->willReturn($result);

        $query = new Query($this->em);
        $query->setDQL('SELECT u FROM Doctrine\Tests\Models\CMS\CmsUser u');

        $paginator = (new CursorPaginator($query, queryProducesDuplicates: false))->setUseOutputWalkers(false);

        self::assertSame(5, $paginator->getTotalCount());
        self::assertSame(5, $paginator->getTotalCount());
    }

    public function testCloneQueryCopiesExistingHints(): void
    {
        $items = [(object) ['id' => 1]];
        $this->hydrator->method('hydrateAll')->willReturn($items);
        $result = $this->createMock(Result::class);
        $this->connection->method('executeQuery')->willReturn($result);

        $query = new Query($this->em);
        $query->setDQL('SELECT p FROM Doctrine\Tests\ORM\Tools\Pagination\BlogPost p ORDER BY p.id ASC');
        $query->setHint('custom.hint', 'custom_value');

        $paginator = new CursorPaginator($query, queryProducesDuplicates: false);
        $paginator->paginate(null, 10);

        self::assertSame(1, $paginator->countPageItems());
    }
}
