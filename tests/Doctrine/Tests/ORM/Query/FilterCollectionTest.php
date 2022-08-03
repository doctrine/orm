<?php

declare(strict_types=1);

namespace Doctrine\Tests\ORM\Query;

use Doctrine\ORM\EntityManager;
use Doctrine\ORM\Mapping\ClassMetadata;
use Doctrine\ORM\Query\Filter\SQLFilter;
use Doctrine\Tests\OrmTestCase;

/**
 * Test case for FilterCollection
 */
class FilterCollectionTest extends OrmTestCase
{
    /** @var EntityManager */
    private $em;

    protected function setUp(): void
    {
        $this->em = $this->getTestEntityManager();
        $this->em->getConfiguration()->addFilter('testFilter', MyFilter::class);
    }

    public function testEnable(): void
    {
        $filterCollection = $this->em->getFilters();

        $this->assertCount(0, $filterCollection->getEnabledFilters());

        $filterCollection->enable('testFilter');

        $enabledFilters = $filterCollection->getEnabledFilters();

        $this->assertCount(1, $enabledFilters);
        $this->assertContainsOnly(MyFilter::class, $enabledFilters);

        $filterCollection->disable('testFilter');
        $this->assertCount(0, $filterCollection->getEnabledFilters());
    }

    public function testHasFilter(): void
    {
        $filterCollection = $this->em->getFilters();

        $this->assertTrue($filterCollection->has('testFilter'));
        $this->assertFalse($filterCollection->has('fakeFilter'));
    }

    /**
     * @depends testEnable
     */
    public function testIsEnabled(): void
    {
        $filterCollection = $this->em->getFilters();

        $this->assertFalse($filterCollection->isEnabled('testFilter'));

        $filterCollection->enable('testFilter');

        $this->assertTrue($filterCollection->isEnabled('testFilter'));
    }

    public function testGetFilterInvalidArgument(): void
    {
        $this->expectException('InvalidArgumentException');
        $filterCollection = $this->em->getFilters();
        $filterCollection->getFilter('testFilter');
    }

    public function testGetFilter(): void
    {
        $filterCollection = $this->em->getFilters();
        $filterCollection->enable('testFilter');

        $this->assertInstanceOf(MyFilter::class, $filterCollection->getFilter('testFilter'));
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
