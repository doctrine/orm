<?php

namespace Doctrine\Tests\ORM\Query;

use Doctrine\ORM\Mapping\ClassMetadata;
use Doctrine\ORM\Query\Filter\SQLFilter;
use Doctrine\Tests\OrmTestCase;

/**
 * Test case for FilterCollection
 *
 * @author Guilherme Blanco <guilhermeblanco@hotmail.com>
 */
class FilterCollectionTest extends OrmTestCase
{
    /**
     * @var \Doctrine\ORM\EntityManager
     */
    private $em;

    protected function setUp()
    {
        $this->em = $this->getTestEntityManager();
        $this->em->getConfiguration()->addFilter('testFilter', MyFilter::class);
    }

    public function testEnable()
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

    public function testHasFilter()
    {
        $filterCollection = $this->em->getFilters();

        self::assertTrue($filterCollection->has('testFilter'));
        self::assertFalse($filterCollection->has('fakeFilter'));
    }

    /**
     * @depends testEnable
     */
    public function testIsEnabled()
    {
        $filterCollection = $this->em->getFilters();

        self::assertFalse($filterCollection->isEnabled('testFilter'));

        $filterCollection->enable('testFilter');

        self::assertTrue($filterCollection->isEnabled('testFilter'));
    }

    /**
     * @expectedException InvalidArgumentException
     */
    public function testGetFilterInvalidArgument()
    {
        $filterCollection = $this->em->getFilters();
        $filterCollection->getFilter('testFilter');
    }

    public function testGetFilter()
    {
        $filterCollection = $this->em->getFilters();
        $filterCollection->enable('testFilter');

        self::assertInstanceOf(MyFilter::class, $filterCollection->getFilter('testFilter'));
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
