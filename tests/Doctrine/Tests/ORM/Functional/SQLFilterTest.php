<?php

namespace Doctrine\Tests\ORM\Functional;

use Doctrine\ORM\Query\Filter\SQLFilter;
use Doctrine\ORM\Mapping\ClassMetaData;
use Doctrine\Common\Cache\ArrayCache;

use Doctrine\ORM\Mapping\ClassMetadataInfo;

use Doctrine\Tests\Models\CMS\CmsUser;
use Doctrine\Tests\Models\CMS\CmsPhonenumber;
use Doctrine\Tests\Models\CMS\CmsAddress;
use Doctrine\Tests\Models\CMS\CmsGroup;
use Doctrine\Tests\Models\CMS\CmsArticle;
use Doctrine\Tests\Models\CMS\CmsComment;

require_once __DIR__ . '/../../TestInit.php';

/**
 * Tests SQLFilter functionality.
 *
 * @author Alexander <iam.asm89@gmail.com>
 */
class SQLFilterTest extends \Doctrine\Tests\OrmFunctionalTestCase
{
    private $userId, $userId2, $articleId, $articleId2;

    public function setUp()
    {
        $this->useModelSet('cms');
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

    protected function getMockEntityManager()
    {
        // Setup connection mock
        $em = $this->getMockBuilder('Doctrine\ORM\EntityManager')
            ->disableOriginalConstructor()
            ->getMock();

        return $em;
    }

    public function testSQLFilterGetSetParameter()
    {
        // Setup mock connection
        $conn = $this->getMockConnection();
        $conn->expects($this->once())
            ->method('quote')
            ->with($this->equalTo('en'))
            ->will($this->returnValue("'en'"));

        $em = $this->getMockEntityManager($conn);
        $em->expects($this->once())
            ->method('getConnection')
            ->will($this->returnValue($conn));

        $filter = new MyLocaleFilter($em);

        $filter->setParameter('locale', 'en', \Doctrine\DBAL\Types\Type::STRING);

        $this->assertEquals("'en'", $filter->getParameter('locale'));
    }

    public function testSQLFilterAddConstraint()
    {
        // Set up metadata mock
        $targetEntity = $this->getMockBuilder('Doctrine\ORM\Mapping\ClassMetadata')
            ->disableOriginalConstructor()
            ->getMock();

        $filter = new MySoftDeleteFilter($this->getMockEntityManager());

        // Test for an entity that gets extra filter data
        $targetEntity->name = 'MyEntity\SoftDeleteNewsItem';
        $this->assertEquals('t1_.deleted = 0', $filter->addFilterConstraint($targetEntity, 't1_'));

        // Test for an entity that doesn't get extra filter data
        $targetEntity->name = 'MyEntity\NoSoftDeleteNewsItem';
        $this->assertEquals('', $filter->addFilterConstraint($targetEntity, 't1_'));

    }

    public function testSQLFilterToString()
    {
        $filter = new MyLocaleFilter($this->getMockEntityManager());
        $filter->setParameter('locale', 'en', \Doctrine\DBAL\Types\Type::STRING);
        $filter->setParameter('foo', 'bar', \Doctrine\DBAL\Types\Type::STRING);

        $filter2 = new MyLocaleFilter($this->getMockEntityManager());
        $filter2->setParameter('foo', 'bar', \Doctrine\DBAL\Types\Type::STRING);
        $filter2->setParameter('locale', 'en', \Doctrine\DBAL\Types\Type::STRING);

        $parameters = array(
            'foo' => array('value' => 'bar', 'type' => \Doctrine\DBAL\Types\Type::STRING),
            'locale' => array('value' => 'en', 'type' => \Doctrine\DBAL\Types\Type::STRING),
        );

        $this->assertEquals(serialize($parameters), ''.$filter);
        $this->assertEquals(''.$filter, ''.$filter2);
    }

    public function testQueryCache_DependsOnFilters()
    {
        $query = $this->_em->createQuery('select ux from Doctrine\Tests\Models\CMS\CmsUser ux');

        $cache = new ArrayCache();
        $query->setQueryCacheDriver($cache);

        $query->getResult();
        $this->assertEquals(1, count($cache->getIds()));

        $conf = $this->_em->getConfiguration();
        $conf->addFilter("locale", "\Doctrine\Tests\ORM\Functional\MyLocaleFilter");
        $this->_em->enableFilter("locale");

        $query->getResult();
        $this->assertEquals(2, count($cache->getIds()));

        // Another time doesn't add another cache entry
        $query->getResult();
        $this->assertEquals(2, count($cache->getIds()));
    }

    public function testQueryGeneration_DependsOnFilters()
    {
        $query = $this->_em->createQuery('select a from Doctrine\Tests\Models\CMS\CmsAddress a');
        $firstSQLQuery = $query->getSQL();

        $conf = $this->_em->getConfiguration();
        $conf->addFilter("country", "\Doctrine\Tests\ORM\Functional\CMSCountryFilter");
        $this->_em->enableFilter("country")
            ->setParameter("country", "en", \Doctrine\DBAL\Types\Type::getType(\Doctrine\DBAL\Types\Type::STRING)->getBindingType());

        $this->assertNotEquals($firstSQLQuery, $query->getSQL());
    }

    public function testToOneFilter()
    {
        //$this->_em->getConnection()->getConfiguration()->setSQLLogger(new \Doctrine\DBAL\Logging\EchoSQLLogger);
        $this->loadFixtureData();

        $query = $this->_em->createQuery('select ux, ua from Doctrine\Tests\Models\CMS\CmsUser ux JOIN ux.address ua');

        // We get two users before enabling the filter
        $this->assertEquals(2, count($query->getResult()));

        $conf = $this->_em->getConfiguration();
        $conf->addFilter("country", "\Doctrine\Tests\ORM\Functional\CMSCountryFilter");
        $this->_em->enableFilter("country")->setParameter("country", "Germany", \Doctrine\DBAL\Types\Type::getType(\Doctrine\DBAL\Types\Type::STRING)->getBindingType());

        // We get one user after enabling the filter
        $this->assertEquals(1, count($query->getResult()));
    }

    public function testManyToManyFilter()
    {
        $this->loadFixtureData();
        $query = $this->_em->createQuery('select ux, ug from Doctrine\Tests\Models\CMS\CmsUser ux JOIN ux.groups ug');

        // We get two users before enabling the filter
        $this->assertEquals(2, count($query->getResult()));

        $conf = $this->_em->getConfiguration();
        $conf->addFilter("group_prefix", "\Doctrine\Tests\ORM\Functional\CMSGroupPrefixFilter");
        $this->_em->enableFilter("group_prefix")->setParameter("prefix", "bar_%", \Doctrine\DBAL\Types\Type::getType(\Doctrine\DBAL\Types\Type::STRING)->getBindingType());

        // We get one user after enabling the filter
        $this->assertEquals(1, count($query->getResult()));

    }

    public function testWhereFilter()
    {
        $this->loadFixtureData();
        $query = $this->_em->createQuery('select ug from Doctrine\Tests\Models\CMS\CmsGroup ug WHERE 1=1');

        // We get two users before enabling the filter
        $this->assertEquals(2, count($query->getResult()));

        $conf = $this->_em->getConfiguration();
        $conf->addFilter("group_prefix", "\Doctrine\Tests\ORM\Functional\CMSGroupPrefixFilter");
        $this->_em->enableFilter("group_prefix")->setParameter("prefix", "bar_%", \Doctrine\DBAL\Types\Type::getType(\Doctrine\DBAL\Types\Type::STRING)->getBindingType());

        // We get one user after enabling the filter
        $this->assertEquals(1, count($query->getResult()));
    }


    private function loadFixtureData()
    {
        $user = new CmsUser;
        $user->name = 'Roman';
        $user->username = 'romanb';
        $user->status = 'developer';

        $address = new CmsAddress;
        $address->country = 'Germany';
        $address->city = 'Berlin';
        $address->zip = '12345';

        $user->address = $address; // inverse side
        $address->user = $user; // owning side!

        $group = new CmsGroup;
        $group->name = 'foo_group';
        $user->addGroup($group);

        $article1 = new CmsArticle;
        $article1->topic = "Test1";
        $article1->text = "Test";
        $article1->setAuthor($user);

        $article2 = new CmsArticle;
        $article2->topic = "Test2";
        $article2->text = "Test";
        $article2->setAuthor($user);

        $this->_em->persist($article1);
        $this->_em->persist($article2);

        $this->_em->persist($user);

        $user2 = new CmsUser;
        $user2->name = 'Guilherme';
        $user2->username = 'gblanco';
        $user2->status = 'developer';

        $address2 = new CmsAddress;
        $address2->country = 'France';
        $address2->city = 'Paris';
        $address2->zip = '12345';

        $user->address = $address2; // inverse side
        $address2->user = $user2; // owning side!

        $user2->addGroup($group);
        $group2 = new CmsGroup;
        $group2->name = 'bar_group';
        $user2->addGroup($group2);

        $this->_em->persist($user2);
        $this->_em->flush();
        $this->_em->clear();

        $this->userId = $user->getId();
        $this->userId2 = $user2->getId();
        $this->articleId = $article1->id;
        $this->articleId2 = $article2->id;
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

        return $targetTableAlias.'.locale = ' . $this->getParameter('locale'); // getParam uses connection to quote the value.
    }
}

class CMSCountryFilter extends SQLFilter
{
    public function addFilterConstraint(ClassMetadata $targetEntity, $targetTableAlias)
    {
        if ($targetEntity->name != "Doctrine\Tests\Models\CMS\CmsAddress") {
            return "";
        }

        return $targetTableAlias.'.country = ' . $this->getParameter('country'); // getParam uses connection to quote the value.
    }
}

class CMSGroupPrefixFilter extends SQLFilter
{
    public function addFilterConstraint(ClassMetadata $targetEntity, $targetTableAlias)
    {
        if ($targetEntity->name != "Doctrine\Tests\Models\CMS\CmsGroup") {
            return "";
        }

        return $targetTableAlias.'.name LIKE ' . $this->getParameter('prefix'); // getParam uses connection to quote the value.
    }
}
class CMSArticleTopicFilter extends SQLFilter
{
    public function addFilterConstraint(ClassMetadata $targetEntity, $targetTableAlias)
    {
        if ($targetEntity->name != "Doctrine\Tests\Models\CMS\CmsArticle") {
            return "";
        }

        return $targetTableAlias.'.topic = ' . $this->getParameter('topic'); // getParam uses connection to quote the value.
    }
}
