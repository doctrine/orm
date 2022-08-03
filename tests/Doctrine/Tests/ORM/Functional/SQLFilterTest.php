<?php

declare(strict_types=1);

namespace Doctrine\Tests\ORM\Functional;

use Doctrine\Common\Cache\ArrayCache;
use Doctrine\DBAL\Connection;
use Doctrine\DBAL\Types\Type as DBALType;
use Doctrine\ORM\Configuration;
use Doctrine\ORM\EntityManager;
use Doctrine\ORM\EntityManagerInterface;
use Doctrine\ORM\Mapping\ClassMetadata;
use Doctrine\ORM\Mapping\ClassMetadataInfo;
use Doctrine\ORM\Query\Filter\SQLFilter;
use Doctrine\ORM\Query\FilterCollection;
use Doctrine\Tests\Models\CMS\CmsAddress;
use Doctrine\Tests\Models\CMS\CmsArticle;
use Doctrine\Tests\Models\CMS\CmsGroup;
use Doctrine\Tests\Models\CMS\CmsUser;
use Doctrine\Tests\Models\Company\CompanyAuction;
use Doctrine\Tests\Models\Company\CompanyContract;
use Doctrine\Tests\Models\Company\CompanyEvent;
use Doctrine\Tests\Models\Company\CompanyFlexContract;
use Doctrine\Tests\Models\Company\CompanyFlexUltraContract;
use Doctrine\Tests\Models\Company\CompanyManager;
use Doctrine\Tests\Models\Company\CompanyOrganization;
use Doctrine\Tests\Models\Company\CompanyPerson;
use Doctrine\Tests\OrmFunctionalTestCase;
use InvalidArgumentException;
use ReflectionMethod;
use ReflectionProperty;

use function count;
use function in_array;
use function serialize;

/**
 * Tests SQLFilter functionality.
 *
 * @group non-cacheable
 */
class SQLFilterTest extends OrmFunctionalTestCase
{
    /** @var int */
    private $userId;

    /** @var int */
    private $userId2;

    /** @var int */
    private $articleId;

    /** @var int */
    private $articleId2;

    /** @var int */
    private $groupId;

    /** @var int */
    private $groupId2;

    /** @var int */
    private $managerId;

    /** @var int */
    private $managerId2;

    /** @var int */
    private $contractId1;

    /** @var int */
    private $contractId2;

    /** @var int */
    private $organizationId;

    /** @var int */
    private $eventId1;

    /** @var int */
    private $eventId2;

    protected function setUp(): void
    {
        $this->useModelSet('cms');
        $this->useModelSet('company');
        parent::setUp();
    }

    public function tearDown(): void
    {
        parent::tearDown();

        $class                                           = $this->_em->getClassMetadata(CmsUser::class);
        $class->associationMappings['groups']['fetch']   = ClassMetadataInfo::FETCH_LAZY;
        $class->associationMappings['articles']['fetch'] = ClassMetadataInfo::FETCH_LAZY;
    }

    public function testConfigureFilter(): void
    {
        $config = new Configuration();

        $config->addFilter('locale', '\Doctrine\Tests\ORM\Functional\MyLocaleFilter');

        $this->assertEquals('\Doctrine\Tests\ORM\Functional\MyLocaleFilter', $config->getFilterClassName('locale'));
        $this->assertNull($config->getFilterClassName('foo'));
    }

    public function testEntityManagerEnableFilter(): void
    {
        $em = $this->getEntityManager();
        $this->configureFilters($em);

        // Enable an existing filter
        $filter = $em->getFilters()->enable('locale');
        $this->assertTrue($filter instanceof MyLocaleFilter);

        // Enable the filter again
        $filter2 = $em->getFilters()->enable('locale');
        $this->assertEquals($filter, $filter2);

        // Enable a non-existing filter
        $exceptionThrown = false;
        try {
            $filter = $em->getFilters()->enable('foo');
        } catch (InvalidArgumentException $e) {
            $exceptionThrown = true;
        }

        $this->assertTrue($exceptionThrown);
    }

    public function testEntityManagerEnabledFilters(): void
    {
        $em = $this->getEntityManager();

        // No enabled filters
        $this->assertEquals([], $em->getFilters()->getEnabledFilters());

        $this->configureFilters($em);
        $filter = $em->getFilters()->enable('locale');
        $filter = $em->getFilters()->enable('soft_delete');

        // Two enabled filters
        $this->assertEquals(2, count($em->getFilters()->getEnabledFilters()));
    }

    public function testEntityManagerDisableFilter(): void
    {
        $em = $this->getEntityManager();
        $this->configureFilters($em);

        // Enable the filter
        $filter = $em->getFilters()->enable('locale');

        // Disable it
        $this->assertEquals($filter, $em->getFilters()->disable('locale'));
        $this->assertEquals(0, count($em->getFilters()->getEnabledFilters()));

        // Disable a non-existing filter
        $exceptionThrown = false;
        try {
            $filter = $em->getFilters()->disable('foo');
        } catch (InvalidArgumentException $e) {
            $exceptionThrown = true;
        }

        $this->assertTrue($exceptionThrown);

        // Disable a non-enabled filter
        $exceptionThrown = false;
        try {
            $filter = $em->getFilters()->disable('locale');
        } catch (InvalidArgumentException $e) {
            $exceptionThrown = true;
        }

        $this->assertTrue($exceptionThrown);
    }

    public function testEntityManagerGetFilter(): void
    {
        $em = $this->getEntityManager();
        $this->configureFilters($em);

        // Enable the filter
        $filter = $em->getFilters()->enable('locale');

        // Get the filter
        $this->assertEquals($filter, $em->getFilters()->getFilter('locale'));

        // Get a non-enabled filter
        $exceptionThrown = false;
        try {
            $filter = $em->getFilters()->getFilter('soft_delete');
        } catch (InvalidArgumentException $e) {
            $exceptionThrown = true;
        }

        $this->assertTrue($exceptionThrown);
    }

    /**
     * @group DDC-2203
     */
    public function testEntityManagerIsFilterEnabled(): void
    {
        $em = $this->getEntityManager();
        $this->configureFilters($em);

        // Check for an enabled filter
        $em->getFilters()->enable('locale');
        $this->assertTrue($em->getFilters()->isEnabled('locale'));

        // Check for a disabled filter
        $em->getFilters()->disable('locale');
        $this->assertFalse($em->getFilters()->isEnabled('locale'));

        // Check a non-existing filter
        $this->assertFalse($em->getFilters()->isEnabled('foo_filter'));
    }

    protected function configureFilters($em): void
    {
        // Add filters to the configuration of the EM
        $config = $em->getConfiguration();
        $config->addFilter('locale', '\Doctrine\Tests\ORM\Functional\MyLocaleFilter');
        $config->addFilter('soft_delete', '\Doctrine\Tests\ORM\Functional\MySoftDeleteFilter');
    }

    protected function getMockConnection(): Connection
    {
        // Setup connection mock
        return $this->createMock(Connection::class);
    }

    protected function getMockEntityManager(): EntityManagerInterface
    {
        // Setup entity manager mock
        return $this->createMock(EntityManager::class);
    }

    protected function addMockFilterCollection(EntityManagerInterface $em): FilterCollection
    {
        $filterCollection = $this->getMockBuilder(FilterCollection::class)
            ->disableOriginalConstructor()
            ->getMock();

        $em->expects($this->any())
            ->method('getFilters')
            ->will($this->returnValue($filterCollection));

        return $filterCollection;
    }

    public function testSQLFilterGetSetParameter(): void
    {
        // Setup mock connection
        $conn = $this->getMockConnection();
        $conn->expects($this->once())
            ->method('quote')
            ->with($this->equalTo('en'))
            ->will($this->returnValue("'en'"));

        $em = $this->getMockEntityManager();
        $em->expects($this->once())
            ->method('getConnection')
            ->will($this->returnValue($conn));

        $filterCollection = $this->addMockFilterCollection($em);
        $filterCollection
            ->expects($this->once())
            ->method('setFiltersStateDirty');

        $filter = new MyLocaleFilter($em);

        $filter->setParameter('locale', 'en', DBALType::STRING);

        $this->assertEquals("'en'", $filter->getParameter('locale'));
    }

    /**
     * @group DDC-3161
     * @group 1054
     */
    public function testSQLFilterGetConnection(): void
    {
        // Setup mock connection
        $conn = $this->getMockConnection();

        $em = $this->getMockEntityManager();
        $em->expects($this->once())
            ->method('getConnection')
            ->will($this->returnValue($conn));

        $filter = new MyLocaleFilter($em);

        $reflMethod = new ReflectionMethod(SQLFilter::class, 'getConnection');
        $reflMethod->setAccessible(true);

        $this->assertSame($conn, $reflMethod->invoke($filter));
    }

    public function testSQLFilterSetParameterInfersType(): void
    {
        // Setup mock connection
        $conn = $this->getMockConnection();
        $conn->expects($this->once())
            ->method('quote')
            ->with($this->equalTo('en'))
            ->will($this->returnValue("'en'"));

        $em = $this->getMockEntityManager();
        $em->expects($this->once())
            ->method('getConnection')
            ->will($this->returnValue($conn));

        $filterCollection = $this->addMockFilterCollection($em);
        $filterCollection
            ->expects($this->once())
            ->method('setFiltersStateDirty');

        $filter = new MyLocaleFilter($em);

        $filter->setParameter('locale', 'en');

        $this->assertEquals("'en'", $filter->getParameter('locale'));
    }

    public function testSQLFilterAddConstraint(): void
    {
        // Set up metadata mock
        $targetEntity = $this->getMockBuilder(ClassMetadata::class)
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

    public function testSQLFilterToString(): void
    {
        $em               = $this->getMockEntityManager();
        $filterCollection = $this->addMockFilterCollection($em);

        $filter = new MyLocaleFilter($em);
        $filter->setParameter('locale', 'en', DBALType::STRING);
        $filter->setParameter('foo', 'bar', DBALType::STRING);

        $filter2 = new MyLocaleFilter($em);
        $filter2->setParameter('foo', 'bar', DBALType::STRING);
        $filter2->setParameter('locale', 'en', DBALType::STRING);

        $parameters = [
            'foo' => ['value' => 'bar', 'type' => DBALType::STRING],
            'locale' => ['value' => 'en', 'type' => DBALType::STRING],
        ];

        $this->assertEquals(serialize($parameters), '' . $filter);
        $this->assertEquals('' . $filter, '' . $filter2);
    }

    public function testQueryCacheDependsOnFilters(): void
    {
        $cacheDataReflection = new ReflectionProperty(ArrayCache::class, 'data');
        $cacheDataReflection->setAccessible(true);

        $query = $this->_em->createQuery('select ux from Doctrine\Tests\Models\CMS\CmsUser ux');

        $cache = new ArrayCache();
        $query->setQueryCacheDriver($cache);

        $query->getResult();
        $this->assertEquals(1, count($cacheDataReflection->getValue($cache)));

        $conf = $this->_em->getConfiguration();
        $conf->addFilter('locale', '\Doctrine\Tests\ORM\Functional\MyLocaleFilter');
        $this->_em->getFilters()->enable('locale');

        $query->getResult();
        $this->assertEquals(2, count($cacheDataReflection->getValue($cache)));

        // Another time doesn't add another cache entry
        $query->getResult();
        $this->assertEquals(2, count($cacheDataReflection->getValue($cache)));
    }

    public function testQueryGenerationDependsOnFilters(): void
    {
        $query         = $this->_em->createQuery('select a from Doctrine\Tests\Models\CMS\CmsAddress a');
        $firstSQLQuery = $query->getSQL();

        $conf = $this->_em->getConfiguration();
        $conf->addFilter('country', '\Doctrine\Tests\ORM\Functional\CMSCountryFilter');
        $this->_em->getFilters()->enable('country')
            ->setParameter('country', 'en', DBALType::STRING);

        $this->assertNotEquals($firstSQLQuery, $query->getSQL());
    }

    public function testRepositoryFind(): void
    {
        $this->loadFixtureData();

        $this->assertNotNull($this->_em->getRepository(CmsGroup::class)->find($this->groupId));
        $this->assertNotNull($this->_em->getRepository(CmsGroup::class)->find($this->groupId2));

        $this->useCMSGroupPrefixFilter();
        $this->_em->clear();

        $this->assertNotNull($this->_em->getRepository(CmsGroup::class)->find($this->groupId));
        $this->assertNull($this->_em->getRepository(CmsGroup::class)->find($this->groupId2));
    }

    public function testRepositoryFindAll(): void
    {
        $this->loadFixtureData();

        $this->assertCount(2, $this->_em->getRepository(CmsGroup::class)->findAll());

        $this->useCMSGroupPrefixFilter();
        $this->_em->clear();

        $this->assertCount(1, $this->_em->getRepository(CmsGroup::class)->findAll());
    }

    public function testRepositoryFindBy(): void
    {
        $this->loadFixtureData();

        $this->assertCount(1, $this->_em->getRepository(CmsGroup::class)->findBy(
            ['id' => $this->groupId2]
        ));

        $this->useCMSGroupPrefixFilter();
        $this->_em->clear();

        $this->assertCount(0, $this->_em->getRepository(CmsGroup::class)->findBy(
            ['id' => $this->groupId2]
        ));
    }

    public function testRepositoryFindByX(): void
    {
        $this->loadFixtureData();

        $this->assertCount(1, $this->_em->getRepository(CmsGroup::class)->findById($this->groupId2));

        $this->useCMSGroupPrefixFilter();
        $this->_em->clear();

        $this->assertCount(0, $this->_em->getRepository(CmsGroup::class)->findById($this->groupId2));
    }

    public function testRepositoryFindOneBy(): void
    {
        $this->loadFixtureData();

        $this->assertNotNull($this->_em->getRepository(CmsGroup::class)->findOneBy(
            ['id' => $this->groupId2]
        ));

        $this->useCMSGroupPrefixFilter();
        $this->_em->clear();

        $this->assertNull($this->_em->getRepository(CmsGroup::class)->findOneBy(
            ['id' => $this->groupId2]
        ));
    }

    public function testRepositoryFindOneByX(): void
    {
        $this->loadFixtureData();

        $this->assertNotNull($this->_em->getRepository(CmsGroup::class)->findOneById($this->groupId2));

        $this->useCMSGroupPrefixFilter();
        $this->_em->clear();

        $this->assertNull($this->_em->getRepository(CmsGroup::class)->findOneById($this->groupId2));
    }

    public function testToOneFilter(): void
    {
        //$this->_em->getConnection()->getConfiguration()->setSQLLogger(new \Doctrine\DBAL\Logging\EchoSQLLogger);
        $this->loadFixtureData();

        $query = $this->_em->createQuery('select ux, ua from Doctrine\Tests\Models\CMS\CmsUser ux JOIN ux.address ua');

        // We get two users before enabling the filter
        $this->assertEquals(2, count($query->getResult()));

        $conf = $this->_em->getConfiguration();
        $conf->addFilter('country', '\Doctrine\Tests\ORM\Functional\CMSCountryFilter');
        $this->_em->getFilters()->enable('country')->setParameter('country', 'Germany', DBALType::STRING);

        // We get one user after enabling the filter
        $this->assertEquals(1, count($query->getResult()));
    }

    public function testManyToManyFilter(): void
    {
        $this->loadFixtureData();
        $query = $this->_em->createQuery('select ux, ug from Doctrine\Tests\Models\CMS\CmsUser ux JOIN ux.groups ug');

        // We get two users before enabling the filter
        $this->assertEquals(2, count($query->getResult()));

        $conf = $this->_em->getConfiguration();
        $conf->addFilter('group_prefix', '\Doctrine\Tests\ORM\Functional\CMSGroupPrefixFilter');
        $this->_em->getFilters()->enable('group_prefix')->setParameter('prefix', 'bar_%', DBALType::STRING);

        // We get one user after enabling the filter
        $this->assertEquals(1, count($query->getResult()));
    }

    public function testWhereFilter(): void
    {
        $this->loadFixtureData();
        $query = $this->_em->createQuery('select ug from Doctrine\Tests\Models\CMS\CmsGroup ug WHERE 1=1');

        // We get two users before enabling the filter
        $this->assertEquals(2, count($query->getResult()));

        $conf = $this->_em->getConfiguration();
        $conf->addFilter('group_prefix', '\Doctrine\Tests\ORM\Functional\CMSGroupPrefixFilter');
        $this->_em->getFilters()->enable('group_prefix')->setParameter('prefix', 'bar_%', DBALType::STRING);

        // We get one user after enabling the filter
        $this->assertEquals(1, count($query->getResult()));
    }

    public function testWhereOrFilter(): void
    {
        $this->loadFixtureData();
        $query = $this->_em->createQuery('select ug from Doctrine\Tests\Models\CMS\CmsGroup ug WHERE 1=1 OR 1=1');

        // We get two users before enabling the filter
        $this->assertEquals(2, count($query->getResult()));

        $conf = $this->_em->getConfiguration();
        $conf->addFilter('group_prefix', '\Doctrine\Tests\ORM\Functional\CMSGroupPrefixFilter');
        $this->_em->getFilters()->enable('group_prefix')->setParameter('prefix', 'bar_%', DBALType::STRING);

        // We get one user after enabling the filter
        $this->assertEquals(1, count($query->getResult()));
    }

    private function loadLazyFixtureData(): void
    {
        $class                                           = $this->_em->getClassMetadata(CmsUser::class);
        $class->associationMappings['articles']['fetch'] = ClassMetadataInfo::FETCH_EXTRA_LAZY;
        $class->associationMappings['groups']['fetch']   = ClassMetadataInfo::FETCH_EXTRA_LAZY;
        $this->loadFixtureData();
    }

    private function useCMSArticleTopicFilter(): void
    {
        $conf = $this->_em->getConfiguration();
        $conf->addFilter('article_topic', '\Doctrine\Tests\ORM\Functional\CMSArticleTopicFilter');
        $this->_em->getFilters()->enable('article_topic')->setParameter('topic', 'Test1', DBALType::STRING);
    }

    public function testOneToManyExtraLazyCountWithFilter(): void
    {
        $this->loadLazyFixtureData();
        $user = $this->_em->find(CmsUser::class, $this->userId);

        $this->assertFalse($user->articles->isInitialized());
        $this->assertEquals(2, count($user->articles));

        $this->useCMSArticleTopicFilter();

        $this->assertEquals(1, count($user->articles));
    }

    public function testOneToManyExtraLazyContainsWithFilter(): void
    {
        $this->loadLazyFixtureData();
        $user            = $this->_em->find(CmsUser::class, $this->userId);
        $filteredArticle = $this->_em->find(CmsArticle::class, $this->articleId2);

        $this->assertFalse($user->articles->isInitialized());
        $this->assertTrue($user->articles->contains($filteredArticle));

        $this->useCMSArticleTopicFilter();

        $this->assertFalse($user->articles->contains($filteredArticle));
    }

    public function testOneToManyExtraLazySliceWithFilter(): void
    {
        $this->loadLazyFixtureData();
        $user = $this->_em->find(CmsUser::class, $this->userId);

        $this->assertFalse($user->articles->isInitialized());
        $this->assertEquals(2, count($user->articles->slice(0, 10)));

        $this->useCMSArticleTopicFilter();

        $this->assertEquals(1, count($user->articles->slice(0, 10)));
    }

    private function useCMSGroupPrefixFilter(): void
    {
        $conf = $this->_em->getConfiguration();
        $conf->addFilter('group_prefix', '\Doctrine\Tests\ORM\Functional\CMSGroupPrefixFilter');
        $this->_em->getFilters()->enable('group_prefix')->setParameter('prefix', 'foo%', DBALType::STRING);
    }

    public function testManyToManyExtraLazyCountWithFilter(): void
    {
        $this->loadLazyFixtureData();

        $user = $this->_em->find(CmsUser::class, $this->userId2);

        $this->assertFalse($user->groups->isInitialized());
        $this->assertEquals(2, count($user->groups));

        $this->useCMSGroupPrefixFilter();

        $this->assertEquals(1, count($user->groups));
    }

    public function testManyToManyExtraLazyContainsWithFilter(): void
    {
        $this->loadLazyFixtureData();
        $user            = $this->_em->find(CmsUser::class, $this->userId2);
        $filteredArticle = $this->_em->find(CmsGroup::class, $this->groupId2);

        $this->assertFalse($user->groups->isInitialized());
        $this->assertTrue($user->groups->contains($filteredArticle));

        $this->useCMSGroupPrefixFilter();

        $this->assertFalse($user->groups->contains($filteredArticle));
    }

    public function testManyToManyExtraLazySliceWithFilter(): void
    {
        $this->loadLazyFixtureData();
        $user = $this->_em->find(CmsUser::class, $this->userId2);

        $this->assertFalse($user->groups->isInitialized());
        $this->assertEquals(2, count($user->groups->slice(0, 10)));

        $this->useCMSGroupPrefixFilter();

        $this->assertEquals(1, count($user->groups->slice(0, 10)));
    }

    private function loadFixtureData(): void
    {
        $user           = new CmsUser();
        $user->name     = 'Roman';
        $user->username = 'romanb';
        $user->status   = 'developer';

        $address          = new CmsAddress();
        $address->country = 'Germany';
        $address->city    = 'Berlin';
        $address->zip     = '12345';

        $user->address = $address; // inverse side
        $address->user = $user; // owning side!

        $group       = new CmsGroup();
        $group->name = 'foo_group';
        $user->addGroup($group);

        $article1        = new CmsArticle();
        $article1->topic = 'Test1';
        $article1->text  = 'Test';
        $article1->setAuthor($user);

        $article2        = new CmsArticle();
        $article2->topic = 'Test2';
        $article2->text  = 'Test';
        $article2->setAuthor($user);

        $this->_em->persist($article1);
        $this->_em->persist($article2);

        $this->_em->persist($user);

        $user2           = new CmsUser();
        $user2->name     = 'Guilherme';
        $user2->username = 'gblanco';
        $user2->status   = 'developer';

        $address2          = new CmsAddress();
        $address2->country = 'France';
        $address2->city    = 'Paris';
        $address2->zip     = '12345';

        $user->address  = $address2; // inverse side
        $address2->user = $user2; // owning side!

        $user2->addGroup($group);
        $group2       = new CmsGroup();
        $group2->name = 'bar_group';
        $user2->addGroup($group2);

        $this->_em->persist($user2);
        $this->_em->flush();
        $this->_em->clear();

        $this->userId     = $user->getId();
        $this->userId2    = $user2->getId();
        $this->articleId  = $article1->id;
        $this->articleId2 = $article2->id;
        $this->groupId    = $group->id;
        $this->groupId2   = $group2->id;
    }

    public function testJoinSubclassPersisterFilterOnlyOnRootTableWhenFetchingSubEntity(): void
    {
        $this->loadCompanyJoinedSubclassFixtureData();
        // Persister
        $this->assertEquals(2, count($this->_em->getRepository(CompanyManager::class)->findAll()));
        // SQLWalker
        $this->assertEquals(2, count($this->_em->createQuery('SELECT cm FROM Doctrine\Tests\Models\Company\CompanyManager cm')->getResult()));

        // Enable the filter
        $this->usePersonNameFilter('Guilh%');

        $managers = $this->_em->getRepository(CompanyManager::class)->findAll();
        $this->assertEquals(1, count($managers));
        $this->assertEquals('Guilherme', $managers[0]->getName());

        $this->assertEquals(1, count($this->_em->createQuery('SELECT cm FROM Doctrine\Tests\Models\Company\CompanyManager cm')->getResult()));
    }

    public function testJoinSubclassPersisterFilterOnlyOnRootTableWhenFetchingRootEntity(): void
    {
        $this->loadCompanyJoinedSubclassFixtureData();
        $this->assertEquals(3, count($this->_em->getRepository(CompanyPerson::class)->findAll()));
        $this->assertEquals(3, count($this->_em->createQuery('SELECT cp FROM Doctrine\Tests\Models\Company\CompanyPerson cp')->getResult()));

        // Enable the filter
        $this->usePersonNameFilter('Guilh%');

        $persons = $this->_em->getRepository(CompanyPerson::class)->findAll();
        $this->assertEquals(1, count($persons));
        $this->assertEquals('Guilherme', $persons[0]->getName());

        $this->assertEquals(1, count($this->_em->createQuery('SELECT cp FROM Doctrine\Tests\Models\Company\CompanyPerson cp')->getResult()));
    }

    private function loadCompanyJoinedSubclassFixtureData(): void
    {
        $manager = new CompanyManager();
        $manager->setName('Roman');
        $manager->setTitle('testlead');
        $manager->setSalary(42);
        $manager->setDepartment('persisters');

        $manager2 = new CompanyManager();
        $manager2->setName('Guilherme');
        $manager2->setTitle('devlead');
        $manager2->setSalary(42);
        $manager2->setDepartment('parsers');

        $person = new CompanyPerson();
        $person->setName('Benjamin');

        $this->_em->persist($manager);
        $this->_em->persist($manager2);
        $this->_em->persist($person);
        $this->_em->flush();
        $this->_em->clear();
    }

    public function testSingleTableInheritanceFilterOnlyOnRootTableWhenFetchingSubEntity(): void
    {
        $this->loadCompanySingleTableInheritanceFixtureData();
        // Persister
        $this->assertEquals(2, count($this->_em->getRepository(CompanyFlexUltraContract::class)->findAll()));
        // SQLWalker
        $this->assertEquals(2, count($this->_em->createQuery('SELECT cfc FROM Doctrine\Tests\Models\Company\CompanyFlexUltraContract cfc')->getResult()));

        // Enable the filter
        $conf = $this->_em->getConfiguration();
        $conf->addFilter('completed_contract', '\Doctrine\Tests\ORM\Functional\CompletedContractFilter');
        $this->_em->getFilters()
            ->enable('completed_contract')
            ->setParameter('completed', true, DBALType::BOOLEAN);

        $this->assertEquals(1, count($this->_em->getRepository(CompanyFlexUltraContract::class)->findAll()));
        $this->assertEquals(1, count($this->_em->createQuery('SELECT cfc FROM Doctrine\Tests\Models\Company\CompanyFlexUltraContract cfc')->getResult()));
    }

    public function testSingleTableInheritanceFilterOnlyOnRootTableWhenFetchingRootEntity(): void
    {
        $this->loadCompanySingleTableInheritanceFixtureData();
        $this->assertEquals(4, count($this->_em->getRepository(CompanyFlexContract::class)->findAll()));
        $this->assertEquals(4, count($this->_em->createQuery('SELECT cfc FROM Doctrine\Tests\Models\Company\CompanyFlexContract cfc')->getResult()));

        // Enable the filter
        $conf = $this->_em->getConfiguration();
        $conf->addFilter('completed_contract', '\Doctrine\Tests\ORM\Functional\CompletedContractFilter');
        $this->_em->getFilters()
            ->enable('completed_contract')
            ->setParameter('completed', true, DBALType::BOOLEAN);

        $this->assertEquals(2, count($this->_em->getRepository(CompanyFlexContract::class)->findAll()));
        $this->assertEquals(2, count($this->_em->createQuery('SELECT cfc FROM Doctrine\Tests\Models\Company\CompanyFlexContract cfc')->getResult()));
    }

    private function loadCompanySingleTableInheritanceFixtureData(): void
    {
        $contract1 = new CompanyFlexUltraContract();
        $contract2 = new CompanyFlexUltraContract();
        $contract2->markCompleted();

        $contract3 = new CompanyFlexContract();
        $contract4 = new CompanyFlexContract();
        $contract4->markCompleted();

        $manager = new CompanyManager();
        $manager->setName('Alexander');
        $manager->setSalary(42);
        $manager->setDepartment('Doctrine');
        $manager->setTitle('Filterer');

        $manager2 = new CompanyManager();
        $manager2->setName('Benjamin');
        $manager2->setSalary(1337);
        $manager2->setDepartment('Doctrine');
        $manager2->setTitle('Maintainer');

        $contract1->addManager($manager);
        $contract2->addManager($manager);
        $contract3->addManager($manager);
        $contract4->addManager($manager);

        $contract1->addManager($manager2);

        $contract1->setSalesPerson($manager);
        $contract2->setSalesPerson($manager);

        $this->_em->persist($manager);
        $this->_em->persist($manager2);
        $this->_em->persist($contract1);
        $this->_em->persist($contract2);
        $this->_em->persist($contract3);
        $this->_em->persist($contract4);
        $this->_em->flush();
        $this->_em->clear();

        $this->managerId   = $manager->getId();
        $this->managerId2  = $manager2->getId();
        $this->contractId1 = $contract1->getId();
        $this->contractId2 = $contract2->getId();
    }

    private function useCompletedContractFilter(): void
    {
        $conf = $this->_em->getConfiguration();
        $conf->addFilter('completed_contract', '\Doctrine\Tests\ORM\Functional\CompletedContractFilter');
        $this->_em->getFilters()
            ->enable('completed_contract')
            ->setParameter('completed', true, DBALType::BOOLEAN);
    }

    public function testManyToManyExtraLazyCountWithFilterOnSTI(): void
    {
        $this->loadCompanySingleTableInheritanceFixtureData();

        $manager = $this->_em->find(CompanyManager::class, $this->managerId);

        $this->assertFalse($manager->managedContracts->isInitialized());
        $this->assertEquals(4, count($manager->managedContracts));

        // Enable the filter
        $this->useCompletedContractFilter();

        $this->assertFalse($manager->managedContracts->isInitialized());
        $this->assertEquals(2, count($manager->managedContracts));
    }

    public function testManyToManyExtraLazyContainsWithFilterOnSTI(): void
    {
        $this->loadCompanySingleTableInheritanceFixtureData();

        $manager   = $this->_em->find(CompanyManager::class, $this->managerId);
        $contract1 = $this->_em->find(CompanyContract::class, $this->contractId1);
        $contract2 = $this->_em->find(CompanyContract::class, $this->contractId2);

        $this->assertFalse($manager->managedContracts->isInitialized());
        $this->assertTrue($manager->managedContracts->contains($contract1));
        $this->assertTrue($manager->managedContracts->contains($contract2));

        // Enable the filter
        $this->useCompletedContractFilter();

        $this->assertFalse($manager->managedContracts->isInitialized());
        $this->assertFalse($manager->managedContracts->contains($contract1));
        $this->assertTrue($manager->managedContracts->contains($contract2));
    }

    public function testManyToManyExtraLazySliceWithFilterOnSTI(): void
    {
        $this->loadCompanySingleTableInheritanceFixtureData();

        $manager = $this->_em->find(CompanyManager::class, $this->managerId);

        $this->assertFalse($manager->managedContracts->isInitialized());
        $this->assertEquals(4, count($manager->managedContracts->slice(0, 10)));

        // Enable the filter
        $this->useCompletedContractFilter();

        $this->assertFalse($manager->managedContracts->isInitialized());
        $this->assertEquals(2, count($manager->managedContracts->slice(0, 10)));
    }

    private function usePersonNameFilter($name): void
    {
        // Enable the filter
        $conf = $this->_em->getConfiguration();
        $conf->addFilter('person_name', '\Doctrine\Tests\ORM\Functional\CompanyPersonNameFilter');
        $this->_em->getFilters()
            ->enable('person_name')
            ->setParameter('name', $name, DBALType::STRING);
    }

    public function testManyToManyExtraLazyCountWithFilterOnCTI(): void
    {
        $this->loadCompanySingleTableInheritanceFixtureData();

        $contract = $this->_em->find(CompanyFlexUltraContract::class, $this->contractId1);

        $this->assertFalse($contract->managers->isInitialized());
        $this->assertEquals(2, count($contract->managers));

        // Enable the filter
        $this->usePersonNameFilter('Benjamin');

        $this->assertFalse($contract->managers->isInitialized());
        $this->assertEquals(1, count($contract->managers));
    }

    public function testManyToManyExtraLazyContainsWithFilterOnCTI(): void
    {
        $this->loadCompanySingleTableInheritanceFixtureData();

        $contract = $this->_em->find(CompanyFlexUltraContract::class, $this->contractId1);
        $manager1 = $this->_em->find(CompanyManager::class, $this->managerId);
        $manager2 = $this->_em->find(CompanyManager::class, $this->managerId2);

        $this->assertFalse($contract->managers->isInitialized());
        $this->assertTrue($contract->managers->contains($manager1));
        $this->assertTrue($contract->managers->contains($manager2));

        // Enable the filter
        $this->usePersonNameFilter('Benjamin');

        $this->assertFalse($contract->managers->isInitialized());
        $this->assertFalse($contract->managers->contains($manager1));
        $this->assertTrue($contract->managers->contains($manager2));
    }

    public function testManyToManyExtraLazySliceWithFilterOnCTI(): void
    {
        $this->loadCompanySingleTableInheritanceFixtureData();

        $contract = $this->_em->find(CompanyFlexUltraContract::class, $this->contractId1);

        $this->assertFalse($contract->managers->isInitialized());
        $this->assertEquals(2, count($contract->managers->slice(0, 10)));

        // Enable the filter
        $this->usePersonNameFilter('Benjamin');

        $this->assertFalse($contract->managers->isInitialized());
        $this->assertEquals(1, count($contract->managers->slice(0, 10)));
    }

    public function testOneToManyExtraLazyCountWithFilterOnSTI(): void
    {
        $this->loadCompanySingleTableInheritanceFixtureData();

        $manager = $this->_em->find(CompanyManager::class, $this->managerId);

        $this->assertFalse($manager->soldContracts->isInitialized());
        $this->assertEquals(2, count($manager->soldContracts));

        // Enable the filter
        $this->useCompletedContractFilter();

        $this->assertFalse($manager->soldContracts->isInitialized());
        $this->assertEquals(1, count($manager->soldContracts));
    }

    public function testOneToManyExtraLazyContainsWithFilterOnSTI(): void
    {
        $this->loadCompanySingleTableInheritanceFixtureData();

        $manager   = $this->_em->find(CompanyManager::class, $this->managerId);
        $contract1 = $this->_em->find(CompanyContract::class, $this->contractId1);
        $contract2 = $this->_em->find(CompanyContract::class, $this->contractId2);

        $this->assertFalse($manager->soldContracts->isInitialized());
        $this->assertTrue($manager->soldContracts->contains($contract1));
        $this->assertTrue($manager->soldContracts->contains($contract2));

        // Enable the filter
        $this->useCompletedContractFilter();

        $this->assertFalse($manager->soldContracts->isInitialized());
        $this->assertFalse($manager->soldContracts->contains($contract1));
        $this->assertTrue($manager->soldContracts->contains($contract2));
    }

    public function testOneToManyExtraLazySliceWithFilterOnSTI(): void
    {
        $this->loadCompanySingleTableInheritanceFixtureData();

        $manager = $this->_em->find(CompanyManager::class, $this->managerId);

        $this->assertFalse($manager->soldContracts->isInitialized());
        $this->assertEquals(2, count($manager->soldContracts->slice(0, 10)));

        // Enable the filter
        $this->useCompletedContractFilter();

        $this->assertFalse($manager->soldContracts->isInitialized());
        $this->assertEquals(1, count($manager->soldContracts->slice(0, 10)));
    }

    private function loadCompanyOrganizationEventJoinedSubclassFixtureData(): void
    {
        $organization = new CompanyOrganization();

        $event1 = new CompanyAuction();
        $event1->setData('foo');

        $event2 = new CompanyAuction();
        $event2->setData('bar');

        $organization->addEvent($event1);
        $organization->addEvent($event2);

        $this->_em->persist($organization);
        $this->_em->flush();
        $this->_em->clear();

        $this->organizationId = $organization->getId();
        $this->eventId1       = $event1->getId();
        $this->eventId2       = $event2->getId();
    }

    private function useCompanyEventIdFilter(): void
    {
        // Enable the filter
        $conf = $this->_em->getConfiguration();
        $conf->addFilter('event_id', CompanyEventFilter::class);
        $this->_em->getFilters()
            ->enable('event_id')
            ->setParameter('id', $this->eventId2);
    }

    public function testOneToManyExtraLazyCountWithFilterOnCTI(): void
    {
        $this->loadCompanyOrganizationEventJoinedSubclassFixtureData();

        $organization = $this->_em->find(CompanyOrganization::class, $this->organizationId);

        $this->assertFalse($organization->events->isInitialized());
        $this->assertEquals(2, count($organization->events));

        // Enable the filter
        $this->useCompanyEventIdFilter();

        $this->assertFalse($organization->events->isInitialized());
        $this->assertEquals(1, count($organization->events));
    }

    public function testOneToManyExtraLazyContainsWithFilterOnCTI(): void
    {
        $this->loadCompanyOrganizationEventJoinedSubclassFixtureData();

        $organization = $this->_em->find(CompanyOrganization::class, $this->organizationId);

        $event1 = $this->_em->find(CompanyEvent::class, $this->eventId1);
        $event2 = $this->_em->find(CompanyEvent::class, $this->eventId2);

        $this->assertFalse($organization->events->isInitialized());
        $this->assertTrue($organization->events->contains($event1));
        $this->assertTrue($organization->events->contains($event2));

        // Enable the filter
        $this->useCompanyEventIdFilter();

        $this->assertFalse($organization->events->isInitialized());
        $this->assertFalse($organization->events->contains($event1));
        $this->assertTrue($organization->events->contains($event2));
    }

    public function testOneToManyExtraLazySliceWithFilterOnCTI(): void
    {
        $this->loadCompanyOrganizationEventJoinedSubclassFixtureData();

        $organization = $this->_em->find(CompanyOrganization::class, $this->organizationId);

        $this->assertFalse($organization->events->isInitialized());
        $this->assertEquals(2, count($organization->events->slice(0, 10)));

        // Enable the filter
        $this->useCompanyEventIdFilter();

        $this->assertFalse($organization->events->isInitialized());
        $this->assertEquals(1, count($organization->events->slice(0, 10)));
    }
}

class MySoftDeleteFilter extends SQLFilter
{
    /** {@inheritDoc} */
    public function addFilterConstraint(ClassMetadata $targetEntity, $targetTableAlias)
    {
        if ($targetEntity->name !== 'MyEntity\SoftDeleteNewsItem') {
            return '';
        }

        return $targetTableAlias . '.deleted = 0';
    }
}

class MyLocaleFilter extends SQLFilter
{
    /** {@inheritDoc} */
    public function addFilterConstraint(ClassMetadata $targetEntity, $targetTableAlias)
    {
        if (! in_array('LocaleAware', $targetEntity->reflClass->getInterfaceNames())) {
            return '';
        }

        return $targetTableAlias . '.locale = ' . $this->getParameter('locale'); // getParam uses connection to quote the value.
    }
}

class CMSCountryFilter extends SQLFilter
{
    /** {@inheritDoc} */
    public function addFilterConstraint(ClassMetadata $targetEntity, $targetTableAlias)
    {
        if ($targetEntity->name !== CmsAddress::class) {
            return '';
        }

        return $targetTableAlias . '.country = ' . $this->getParameter('country'); // getParam uses connection to quote the value.
    }
}

class CMSGroupPrefixFilter extends SQLFilter
{
    /** {@inheritDoc} */
    public function addFilterConstraint(ClassMetadata $targetEntity, $targetTableAlias)
    {
        if ($targetEntity->name !== CmsGroup::class) {
            return '';
        }

        return $targetTableAlias . '.name LIKE ' . $this->getParameter('prefix'); // getParam uses connection to quote the value.
    }
}

class CMSArticleTopicFilter extends SQLFilter
{
    /** {@inheritDoc} */
    public function addFilterConstraint(ClassMetadata $targetEntity, $targetTableAlias)
    {
        if ($targetEntity->name !== CmsArticle::class) {
            return '';
        }

        return $targetTableAlias . '.topic = ' . $this->getParameter('topic'); // getParam uses connection to quote the value.
    }
}

class CompanyPersonNameFilter extends SQLFilter
{
    /** {@inheritDoc} */
    public function addFilterConstraint(ClassMetadata $targetEntity, $targetTableAlias, $targetTable = '')
    {
        if ($targetEntity->name !== CompanyPerson::class) {
            return '';
        }

        return $targetTableAlias . '.name LIKE ' . $this->getParameter('name');
    }
}

class CompletedContractFilter extends SQLFilter
{
    /** {@inheritDoc} */
    public function addFilterConstraint(ClassMetadata $targetEntity, $targetTableAlias, $targetTable = '')
    {
        if ($targetEntity->name !== CompanyContract::class) {
            return '';
        }

        return $targetTableAlias . '.completed = ' . $this->getParameter('completed');
    }
}

class CompanyEventFilter extends SQLFilter
{
    /** {@inheritDoc} */
    public function addFilterConstraint(ClassMetadata $targetEntity, $targetTableAlias, $targetTable = '')
    {
        if ($targetEntity->name !== CompanyEvent::class) {
            return '';
        }

        return $targetTableAlias . '.id = ' . $this->getParameter('id');
    }
}
