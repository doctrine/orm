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
        $this->em = $this->_getTestEntityManager();
        $this->em->getConfiguration()->addFilter('testFilter', MyFilter::class);
    }

    public function testEnable()
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

    public function testHasFilter()
    {
        $filterCollection = $this->em->getFilters();

        $this->assertTrue($filterCollection->has('testFilter'));
        $this->assertFalse($filterCollection->has('fakeFilter'));
    }

    /**
     * @depends testEnable
     */
    public function testIsEnabled()
    {
        $filterCollection = $this->em->getFilters();

        $this->assertFalse($filterCollection->isEnabled('testFilter'));

        $filterCollection->enable('testFilter');

        $this->assertTrue($filterCollection->isEnabled('testFilter'));
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

        $this->assertInstanceOf(MyFilter::class, $filterCollection->getFilter('testFilter'));
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
