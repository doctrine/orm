<?php

declare(strict_types=1);

namespace Doctrine\Tests\ORM\Query;

use Doctrine\ORM\EntityManagerInterface;
use Doctrine\ORM\Mapping\ClassMetadata;
use Doctrine\ORM\Query\Filter\SQLFilter;
use Doctrine\Tests\OrmTestCase;

/**
 * Test case for FilterCollection
 */
class FilterCollectionTest extends OrmTestCase
{
    private const TEST_FILTER = 'testFilter';

    /** @var EntityManagerInterface */
    private $em;

    protected function setUp() : void
    {
        $this->em = $this->getTestEntityManager();
        $this->em->getConfiguration()->addFilter(self::TEST_FILTER, MyFilter::class);
    }

    public function testEnable() : void
    {
        $filterCollection = $this->em->getFilters();

        self::assertCount(0, $filterCollection->getEnabledFilters());

        $filterCollection->enable(self::TEST_FILTER);

        $enabledFilters = $filterCollection->getEnabledFilters();

        self::assertCount(1, $enabledFilters);
        self::assertContainsOnly(MyFilter::class, $enabledFilters);

        $filterCollection->disable(self::TEST_FILTER);
        self::assertCount(0, $filterCollection->getEnabledFilters());
    }

    public function testHasFilter() : void
    {
        $filterCollection = $this->em->getFilters();

        self::assertTrue($filterCollection->has(self::TEST_FILTER));
        self::assertFalse($filterCollection->has('fakeFilter'));
    }

    /**
     * Should allow to disable filter.
     */
    public function testShouldAllowToDisableFilter()
    {
        $filterCollection = $this->em->getFilters();
        self::assertFalse($filterCollection->isEnabled(self::TEST_FILTER));

        $filterCollection->enable(self::TEST_FILTER);

        self::assertTrue($filterCollection->isEnabled(self::TEST_FILTER));
        $filterCollection->disable(self::TEST_FILTER);
        self::assertFalse($filterCollection->isEnabled(self::TEST_FILTER));
    }

    /**
     * @depends testEnable
     */
    public function testIsEnabled() : void
    {
        $filterCollection = $this->em->getFilters();

        self::assertFalse($filterCollection->isEnabled(self::TEST_FILTER));

        $filterCollection->enable(self::TEST_FILTER);

        self::assertTrue($filterCollection->isEnabled(self::TEST_FILTER));
    }

    public function testGetFilterInvalidArgument() : void
    {
        $this->expectException('InvalidArgumentException');
        $filterCollection = $this->em->getFilters();
        $filterCollection->getFilter(self::TEST_FILTER);
    }

    public function testGetFilter() : void
    {
        $filterCollection = $this->em->getFilters();
        $filterCollection->enable(self::TEST_FILTER);

        self::assertInstanceOf(MyFilter::class, $filterCollection->getFilter(self::TEST_FILTER));
    }
}

class MyFilter extends SQLFilter
{
    public function addFilterConstraint(ClassMetadata $targetEntity, $targetTableAlias)
    {
        // getParameter applies quoting automatically
        return $targetTableAlias . '.id = ' . $this->getParameter('id');
    }
}
