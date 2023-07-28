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
    /** @var EntityManagerMock */
    private $em;

    protected function setUp(): void
    {
        $this->em = $this->getTestEntityManager();
        $this->em->getConfiguration()->addFilter('testFilter', MyFilter::class);
    }

    public function testEnable(): void
    {
        $filterCollection = $this->em->getFilters();

        self::assertCount(0, $filterCollection->getEnabledFilters());

        $filterCollection->enable('testFilter');

        $enabledFilters = $filterCollection->getEnabledFilters();

        self::assertCount(1, $enabledFilters);
        self::assertContainsOnly(MyFilter::class, $enabledFilters);

        $filterCollection->disable('testFilter');
        self::assertCount(0, $filterCollection->getEnabledFilters());
    }

    public function testHasFilter(): void
    {
        $filterCollection = $this->em->getFilters();

        self::assertTrue($filterCollection->has('testFilter'));
        self::assertFalse($filterCollection->has('fakeFilter'));
    }

    /** @depends testEnable */
    public function testIsEnabled(): void
    {
        $filterCollection = $this->em->getFilters();

        self::assertFalse($filterCollection->isEnabled('testFilter'));

        $filterCollection->enable('testFilter');

        self::assertTrue($filterCollection->isEnabled('testFilter'));
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

        $filterCollection->disable('testFilter');

        self::assertFalse($filterCollection->isClean());
        self::assertSame($oldHash, $filterCollection->getHash());
        self::assertTrue($filterCollection->isClean());
    }
}

class MyFilter extends SQLFilter
{
    /**
     * {@inheritDoc}
     */
    public function addFilterConstraint(ClassMetadata $targetEntity, $targetTableAlias): string
    {
        // getParameter applies quoting automatically
        return $targetTableAlias . '.id = ' . $this->getParameter('id');
    }
}
