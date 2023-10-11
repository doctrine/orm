<?php

declare(strict_types=1);

namespace Doctrine\Tests\ORM\Query;

use Doctrine\ORM\Mapping\ClassMetadata;
use Doctrine\ORM\Query\Filter\SQLFilter;
use Doctrine\Tests\Mocks\EntityManagerMock;
use Doctrine\Tests\OrmTestCase;
use InvalidArgumentException;

/**
 * Test case for FilterCollection
 */
class FilterCollectionTest extends OrmTestCase
{
    private EntityManagerMock $em;

    protected function setUp(): void
    {
        $this->em = $this->getTestEntityManager();
        $this->em->getConfiguration()->addFilter('testFilter', MyFilter::class);
    }

    public function testEnable(): void
    {
        $filterCollection = $this->em->getFilters();

        self::assertCount(0, $filterCollection->getEnabledFilters());

        $filter1 = $filterCollection->enable('testFilter');

        $enabledFilters = $filterCollection->getEnabledFilters();

        self::assertCount(1, $enabledFilters);
        self::assertContainsOnly(MyFilter::class, $enabledFilters);

        $filter2 = $filterCollection->disable('testFilter');
        self::assertCount(0, $filterCollection->getEnabledFilters());
        self::assertSame($filter1, $filter2);

        $filter3 = $filterCollection->enable('testFilter');
        self::assertNotSame($filter1, $filter3);

        $filter4 = $filterCollection->suspend('testFilter');
        self::assertSame($filter3, $filter4);

        $filter5 = $filterCollection->enable('testFilter');
        self::assertNotSame($filter4, $filter5);

        self::assertCount(1, $enabledFilters);
        self::assertContainsOnly(MyFilter::class, $enabledFilters);
    }

    public function testSuspend(): void
    {
        $filterCollection = $this->em->getFilters();

        self::assertCount(0, $filterCollection->getEnabledFilters());

        $filter1 = $filterCollection->enable('testFilter');
        self::assertCount(1, $filterCollection->getEnabledFilters());

        $filter2 = $filterCollection->suspend('testFilter');
        self::assertSame($filter1, $filter2);
        self::assertCount(0, $filterCollection->getEnabledFilters());

        $filter3 = $filterCollection->restore('testFilter');
        self::assertSame($filter1, $filter3);
        self::assertCount(1, $filterCollection->getEnabledFilters());
    }

    public function testRestoreFailure(): void
    {
        $filterCollection = $this->em->getFilters();

        $this->expectException(InvalidArgumentException::class);
        $filterCollection->suspend('testFilter');
    }

    public function testHasFilter(): void
    {
        $filterCollection = $this->em->getFilters();

        self::assertTrue($filterCollection->has('testFilter'));
        self::assertFalse($filterCollection->has('fakeFilter'));
    }

    public function testIsEnabled(): void
    {
        $filterCollection = $this->em->getFilters();

        self::assertFalse($filterCollection->isEnabled('testFilter'));

        $filterCollection->enable('testFilter');

        self::assertTrue($filterCollection->isEnabled('testFilter'));

        $filterCollection->suspend('testFilter');

        self::assertFalse($filterCollection->isEnabled('testFilter'));

        $filterCollection->restore('testFilter');

        self::assertTrue($filterCollection->isEnabled('testFilter'));

        self::assertFalse($filterCollection->isEnabled('wrongFilter'));
    }

    public function testIsSuspended(): void
    {
        $filterCollection = $this->em->getFilters();

        self::assertFalse($filterCollection->isSuspended('testFilter'));

        $filterCollection->enable('testFilter');

        self::assertFalse($filterCollection->isSuspended('testFilter'));

        $filterCollection->suspend('testFilter');

        self::assertTrue($filterCollection->isSuspended('testFilter'));

        $filterCollection->restore('testFilter');

        self::assertFalse($filterCollection->isSuspended('testFilter'));

        $filterCollection->disable('testFilter');

        self::assertFalse($filterCollection->isSuspended('testFilter'));

        self::assertFalse($filterCollection->isSuspended('wrongFilter'));
    }

    public function testGetFilterInvalidArgument(): void
    {
        $this->expectException(InvalidArgumentException::class);
        $filterCollection = $this->em->getFilters();
        $filterCollection->getFilter('testFilter');
    }

    public function testGetFilter(): void
    {
        $filterCollection = $this->em->getFilters();
        $filterCollection->enable('testFilter');

        self::assertInstanceOf(MyFilter::class, $filterCollection->getFilter('testFilter'));

        $filterCollection->suspend('testFilter');

        $this->expectException(InvalidArgumentException::class);
        $filterCollection->getFilter('testFilter');
    }

    public function testHashing(): void
    {
        $filterCollection = $this->em->getFilters();

        self::assertTrue($filterCollection->isClean());

        $oldHash = $filterCollection->getHash();
        $filterCollection->setFiltersStateDirty();

        self::assertFalse($filterCollection->isClean());
        self::assertSame($oldHash, $filterCollection->getHash());
        self::assertTrue($filterCollection->isClean());

        $filterCollection->enable('testFilter');

        self::assertFalse($filterCollection->isClean());

        $hash = $filterCollection->getHash();

        self::assertNotSame($oldHash, $hash);
        self::assertTrue($filterCollection->isClean());
        self::assertSame($hash, $filterCollection->getHash());

        $filterCollection->suspend('testFilter');

        self::assertFalse($filterCollection->isClean());
        self::assertSame($oldHash, $filterCollection->getHash());
        self::assertTrue($filterCollection->isClean());

        $filterCollection->restore('testFilter');

        self::assertFalse($filterCollection->isClean());
        self::assertSame($hash, $filterCollection->getHash());
        self::assertTrue($filterCollection->isClean());

        $filterCollection->disable('testFilter');

        self::assertFalse($filterCollection->isClean());
        self::assertSame($oldHash, $filterCollection->getHash());
        self::assertTrue($filterCollection->isClean());
    }
}

class MyFilter extends SQLFilter
{
    public function addFilterConstraint(ClassMetadata $targetEntity, string $targetTableAlias): string
    {
        // getParameter applies quoting automatically
        return $targetTableAlias . '.id = ' . $this->getParameter('id');
    }
}
