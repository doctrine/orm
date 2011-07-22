<?php

namespace Doctrine\Tests\ORM\Functional;

use Doctrine\ORM\Query\Filter\SQLFilter;
use Doctrine\ORM\Mapping\ClassMetaData;

require_once __DIR__ . '/../../TestInit.php';

/**
 * Tests SQLFilter functionality.
 *
 * @author Alexander <iam.asm89@gmail.com>
 */
class SQLFilterTest extends \Doctrine\Tests\OrmFunctionalTestCase
{
    public function setUp()
    {
        parent::setUp();
    }

    public function testConfigureFilter()
    {
        $config = new \Doctrine\ORM\Configuration();

        $config->addFilter("locale", "\Doctrine\Tests\ORM\Functional\MyLocaleFilter");

        $this->assertEquals("\Doctrine\Tests\ORM\Functional\MyLocaleFilter", $config->getFilterClassName("locale"));
        $this->assertNull($config->getFilterClassName("foo"));
    }

    public function testEntityManagerEnableFilter()
    {
        $em = $this->_getEntityManager();
        $this->configureFilters($em);

        // Enable an existing filter
        $filter = $em->enableFilter("locale");
        $this->assertTrue($filter instanceof \Doctrine\Tests\ORM\Functional\MyLocaleFilter);

        // Enable the filter again
        $filter2 = $em->enableFilter("locale");
        $this->assertEquals($filter, $filter2);

        // Enable a non-existing filter
        $exceptionThrown = false;
        try {
            $filter = $em->enableFilter("foo");
        } catch (\InvalidArgumentException $e) {
            $exceptionThrown = true;
        }
        $this->assertTrue($exceptionThrown);
    }

    public function testEntityManagerEnabledFilters()
    {
        $em = $this->_getEntityManager();

        // No enabled filters
        $this->assertEquals(array(), $em->getEnabledFilters());

        $this->configureFilters($em);
        $filter = $em->enableFilter("locale");
        $filter = $em->enableFilter("soft_delete");

        // Two enabled filters
        $this->assertEquals(2, count($em->getEnabledFilters()));

    }

    public function testEntityManagerDisableFilter()
    {
        $em = $this->_getEntityManager();
        $this->configureFilters($em);

        // Enable the filter
        $filter = $em->enableFilter("locale");

        // Disable it
        $this->assertEquals($filter, $em->disableFilter("locale"));
        $this->assertEquals(0, count($em->getEnabledFilters()));

        // Disable a non-existing filter
        $exceptionThrown = false;
        try {
            $filter = $em->disableFilter("foo");
        } catch (\InvalidArgumentException $e) {
            $exceptionThrown = true;
        }
        $this->assertTrue($exceptionThrown);

        // Disable a non-enabled filter
        $exceptionThrown = false;
        try {
            $filter = $em->disableFilter("locale");
        } catch (\InvalidArgumentException $e) {
            $exceptionThrown = true;
        }
        $this->assertTrue($exceptionThrown);
    }

    public function testEntityManagerGetFilter()
    {
        $em = $this->_getEntityManager();
        $this->configureFilters($em);

        // Enable the filter
        $filter = $em->enableFilter("locale");

        // Get the filter
        $this->assertEquals($filter, $em->getFilter("locale"));

        // Get a non-enabled filter
        $exceptionThrown = false;
        try {
            $filter = $em->getFilter("soft_delete");
        } catch (\InvalidArgumentException $e) {
            $exceptionThrown = true;
        }
        $this->assertTrue($exceptionThrown);
    }

    protected function configureFilters($em)
    {
        // Add filters to the configuration of the EM
        $config = $em->getConfiguration();
        $config->addFilter("locale", "\Doctrine\Tests\ORM\Functional\MyLocaleFilter");
        $config->addFilter("soft_delete", "\Doctrine\Tests\ORM\Functional\MySoftDeleteFilter");
    }

    protected function getMockConnection()
    {
        // Setup connection mock
        $conn = $this->getMockBuilder('Doctrine\DBAL\Connection')
            ->disableOriginalConstructor()
            ->getMock();

        return $conn;
    }

    public function testSQLFilterGetSetParameter()
    {
        // Setup mock connection
        $conn = $this->getMockConnection();
        $conn->expects($this->once())
            ->method('quote')
            ->with($this->equalTo('en'))
            ->will($this->returnValue("'en'"));

        $filter = new MyLocaleFilter($conn);

        $filter->setParameter('locale', 'en', \Doctrine\DBAL\Types\Type::STRING);

        $this->assertEquals("'en'", $filter->getParameter('locale'));
    }

    public function testSQLFilterAddConstraint()
    {
        // Set up metadata mock
        $targetEntity = $this->getMockBuilder('Doctrine\ORM\Mapping\ClassMetadata')
            ->disableOriginalConstructor()
            ->getMock();

        $filter = new MySoftDeleteFilter($this->getMockConnection());

        // Test for an entity that gets extra filter data
        $targetEntity->name = 'MyEntity\SoftDeleteNewsItem';
        $this->assertEquals('t1_.deleted = 0', $filter->addFilterConstraint($targetEntity, 't1_'));

        // Test for an entity that doesn't get extra filter data
        $targetEntity->name = 'MyEntity\NoSoftDeleteNewsItem';
        $this->assertEquals('', $filter->addFilterConstraint($targetEntity, 't1_'));

    }

    public function testSQLFilterToString()
    {
        $filter = new MyLocaleFilter($this->getMockConnection());
        $filter->setParameter('locale', 'en', \Doctrine\DBAL\Types\Type::STRING);
        $filter->setParameter('foo', 'bar', \Doctrine\DBAL\Types\Type::STRING);

        $filter2 = new MyLocaleFilter($this->getMockConnection());
        $filter2->setParameter('foo', 'bar', \Doctrine\DBAL\Types\Type::STRING);
        $filter2->setParameter('locale', 'en', \Doctrine\DBAL\Types\Type::STRING);

        $parameters = array(
            'foo' => array('value' => 'bar', 'type' => \Doctrine\DBAL\Types\Type::STRING),
            'locale' => array('value' => 'en', 'type' => \Doctrine\DBAL\Types\Type::STRING),
        );

        $this->assertEquals(serialize($parameters), ''.$filter);
        $this->assertEquals(''.$filter, ''.$filter2);
    }
}

class MySoftDeleteFilter extends SQLFilter
{
    public function addFilterConstraint(ClassMetadata $targetEntity, $targetTableAlias)
    {
        if ($targetEntity->name != "MyEntity\SoftDeleteNewsItem") {
            return "";
        }

        return $targetTableAlias.'.deleted = 0';
    }
}

class MyLocaleFilter extends SQLFilter
{
    public function addFilterConstraint(ClassMetadata $targetEntity, $targetTableAlias)
    {
        if (!in_array("LocaleAware", $targetEntity->reflClass->getInterfaceNames())) {
            return "";
        }

        return $targetTableAlias.'.locale = ' . $this->getParam('locale'); // getParam uses connection to quote the value.
    }
}
