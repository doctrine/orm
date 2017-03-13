<?php

namespace Doctrine\Tests\ORM\Mapping;

use Doctrine\Common\EventManager;
use Doctrine\Common\Persistence\Mapping\Driver\MappingDriver;
use Doctrine\Common\Persistence\Mapping\RuntimeReflectionService;
use Doctrine\DBAL\Connection;
use Doctrine\DBAL\Types\Type;
use Doctrine\ORM\Configuration;
use Doctrine\ORM\EntityManager;
use Doctrine\ORM\EntityManagerInterface;
use Doctrine\ORM\Event\OnClassMetadataNotFoundEventArgs;
use Doctrine\ORM\Events;
use Doctrine\ORM\Sequencing\Generator;
use Doctrine\ORM\Mapping;
use Doctrine\ORM\Mapping\ClassMetadata;
use Doctrine\ORM\Mapping\ClassMetadataFactory;
use Doctrine\ORM\Mapping\MappingException;
use Doctrine\ORM\ORMException;
use Doctrine\Tests\Mocks\ConnectionMock;
use Doctrine\Tests\Mocks\DriverMock;
use Doctrine\Tests\Mocks\EntityManagerMock;
use Doctrine\Tests\Mocks\MetadataDriverMock;
use Doctrine\Tests\Models\CMS\CmsArticle;
use Doctrine\Tests\Models\CMS\CmsUser;
use Doctrine\Tests\Models\DDC4006\DDC4006User;
use Doctrine\Tests\Models\JoinedInheritanceType\AnotherChildClass;
use Doctrine\Tests\Models\JoinedInheritanceType\ChildClass;
use Doctrine\Tests\Models\JoinedInheritanceType\RootClass;
use Doctrine\Tests\Models\Quote;
use Doctrine\Tests\OrmTestCase;
use DoctrineGlobal_Article;

class ClassMetadataFactoryTest extends OrmTestCase
{
    public function testGetMetadataForSingleClass()
    {
        $mockDriver = new MetadataDriverMock();
        $entityManager = $this->createEntityManager($mockDriver);

        $conn = $entityManager->getConnection();
        $mockPlatform = $conn->getDatabasePlatform();
        $mockPlatform->setPrefersSequences(true);
        $mockPlatform->setPrefersIdentityColumns(false);

        $cm1 = $this->createValidClassMetadata();

        // SUT
        $cmf = new ClassMetadataFactory();
        $cmf->setEntityManager($entityManager);
        $cmf->setMetadataFor($cm1->name, $cm1);

        // Prechecks
        self::assertEquals([], $cm1->parentClasses);
        self::assertEquals(Mapping\InheritanceType::NONE, $cm1->inheritanceType);
        self::assertEquals(Mapping\GeneratorType::AUTO, $cm1->generatorType);
        self::assertTrue($cm1->hasField('name'));
        self::assertEquals(2, count($cm1->associationMappings));
        self::assertEquals('group', $cm1->table->getName());

        // Go
        $cmMap1 = $cmf->getMetadataFor($cm1->name);

        self::assertSame($cm1, $cmMap1);
        self::assertEquals('group', $cmMap1->table->getName());
        self::assertEquals([], $cmMap1->parentClasses);
        self::assertTrue($cmMap1->hasField('name'));
    }

    public function testGetMetadataFor_ReturnsLoadedCustomIdGenerator()
    {
        $cm1 = $this->createValidClassMetadata();

        $cm1->setIdGeneratorType(Mapping\GeneratorType::CUSTOM);

        $cm1->generatorDefinition = [
            'class' => CustomIdGenerator::class,
            'arguments' => [],
        ];

        $cmf = $this->createTestFactory();

        $cmf->setMetadataForClass($cm1->name, $cm1);

        $actual = $cmf->getMetadataFor($cm1->name);

        self::assertEquals(Mapping\GeneratorType::CUSTOM, $actual->generatorType);
        self::assertInstanceOf(CustomIdGenerator::class, $actual->idGenerator);
    }

    public function testGetMetadataFor_ThrowsExceptionOnUnknownCustomGeneratorClass()
    {
        $cm1 = $this->createValidClassMetadata();

        $cm1->setIdGeneratorType(Mapping\GeneratorType::CUSTOM);

        $cm1->generatorDefinition = [
            'class' => 'NotExistingGenerator',
            'arguments' => [],
        ];

        $cmf = $this->createTestFactory();

        $cmf->setMetadataForClass($cm1->name, $cm1);

        $this->expectException(ORMException::class);

        $actual = $cmf->getMetadataFor($cm1->name);
    }

    public function testGetMetadataFor_ThrowsExceptionOnMissingCustomGeneratorDefinition()
    {
        $cm1 = $this->createValidClassMetadata();
        $cm1->setIdGeneratorType(Mapping\GeneratorType::CUSTOM);
        $cmf = $this->createTestFactory();
        $cmf->setMetadataForClass($cm1->name, $cm1);
        $this->expectException(ORMException::class);

        $actual = $cmf->getMetadataFor($cm1->name);
    }

    public function testHasGetMetadata_NamespaceSeparatorIsNotNormalized()
    {
        require_once __DIR__."/../../Models/Global/GlobalNamespaceModel.php";

        $metadataDriver = $this->createAnnotationDriver([__DIR__ . '/../../Models/Global/']);

        $entityManager = $this->createEntityManager($metadataDriver);

        $mf = $entityManager->getMetadataFactory();
        $m1 = $mf->getMetadataFor(DoctrineGlobal_Article::class);
        $h1 = $mf->hasMetadataFor(DoctrineGlobal_Article::class);
        $h2 = $mf->hasMetadataFor('\\' . DoctrineGlobal_Article::class);
        $m2 = $mf->getMetadataFor('\\' . DoctrineGlobal_Article::class);

        self::assertNotSame($m1, $m2);
        self::assertFalse($h2);
        self::assertTrue($h1);
    }

    /**
     * @group DDC-1512
     */
    public function testIsTransient()
    {
        $cmf = new ClassMetadataFactory();
        $driver = $this->createMock(MappingDriver::class);
        $driver->expects($this->at(0))
               ->method('isTransient')
               ->with($this->equalTo(CmsUser::class))
               ->will($this->returnValue(true));
        $driver->expects($this->at(1))
               ->method('isTransient')
               ->with($this->equalTo(CmsArticle::class))
               ->will($this->returnValue(false));

        $em = $this->createEntityManager($driver);

        self::assertTrue($em->getMetadataFactory()->isTransient(CmsUser::class));
        self::assertFalse($em->getMetadataFactory()->isTransient(CmsArticle::class));
    }

    /**
     * @group DDC-1512
     */
    public function testIsTransientEntityNamespace()
    {
        $cmf = new ClassMetadataFactory();
        $driver = $this->createMock(MappingDriver::class);
        $driver->expects($this->at(0))
               ->method('isTransient')
               ->with($this->equalTo(CmsUser::class))
               ->will($this->returnValue(true));
        $driver->expects($this->at(1))
               ->method('isTransient')
               ->with($this->equalTo(CmsArticle::class))
               ->will($this->returnValue(false));

        $em = $this->createEntityManager($driver);
        $em->getConfiguration()->addEntityNamespace('CMS', 'Doctrine\Tests\Models\CMS');

        self::assertTrue($em->getMetadataFactory()->isTransient('CMS:CmsUser'));
        self::assertFalse($em->getMetadataFactory()->isTransient('CMS:CmsArticle'));
    }

    public function testAddDefaultDiscriminatorMap()
    {
        $cmf = new ClassMetadataFactory();
        $driver = $this->createAnnotationDriver([__DIR__ . '/../../Models/JoinedInheritanceType/']);
        $em = $this->createEntityManager($driver);
        $cmf->setEntityManager($em);

        $rootMetadata = $cmf->getMetadataFor(RootClass::class);
        $childMetadata = $cmf->getMetadataFor(ChildClass::class);
        $anotherChildMetadata = $cmf->getMetadataFor(AnotherChildClass::class);
        $rootDiscriminatorMap = $rootMetadata->discriminatorMap;
        $childDiscriminatorMap = $childMetadata->discriminatorMap;
        $anotherChildDiscriminatorMap = $anotherChildMetadata->discriminatorMap;

        $rootClass = RootClass::class;
        $childClass = ChildClass::class;
        $anotherChildClass = AnotherChildClass::class;

        $rootClassKey = array_search($rootClass, $rootDiscriminatorMap);
        $childClassKey = array_search($childClass, $rootDiscriminatorMap);
        $anotherChildClassKey = array_search($anotherChildClass, $rootDiscriminatorMap);

        self::assertEquals('rootclass', $rootClassKey);
        self::assertEquals('childclass', $childClassKey);
        self::assertEquals('anotherchildclass', $anotherChildClassKey);

        self::assertEquals($childDiscriminatorMap, $rootDiscriminatorMap);
        self::assertEquals($anotherChildDiscriminatorMap, $rootDiscriminatorMap);

        // ClassMetadataFactory::addDefaultDiscriminatorMap shouldn't be called again, because the
        // discriminator map is already cached
        $cmf = $this->getMockBuilder(ClassMetadataFactory::class)->setMethods(['addDefaultDiscriminatorMap'])->getMock();
        $cmf->setEntityManager($em);
        $cmf->expects($this->never())
            ->method('addDefaultDiscriminatorMap');

        $rootMetadata = $cmf->getMetadataFor(RootClass::class);
    }

    public function testGetAllMetadataWorksWithBadConnection()
    {
        // DDC-3551
        $conn = $this->createMock(Connection::class);
        $mockDriver    = new MetadataDriverMock();
        $em = $this->createEntityManager($mockDriver, $conn);

        $conn->expects($this->any())
            ->method('getDatabasePlatform')
            ->will($this->throwException(new \Exception('Exception thrown in test when calling getDatabasePlatform')));

        $cmf = new ClassMetadataFactory();
        $cmf->setEntityManager($em);

        // getting all the metadata should work, even if get DatabasePlatform blows up
        $metadata = $cmf->getAllMetadata();
        // this will just be an empty array - there was no error
        self::assertEquals([], $metadata);
    }

    protected function createEntityManager($metadataDriver, $conn = null)
    {
        $driverMock = new DriverMock();
        $config = new Configuration();
        $config->setProxyDir(__DIR__ . '/../../Proxies');
        $config->setProxyNamespace('Doctrine\Tests\Proxies');
        $eventManager = new EventManager();
        if (!$conn) {
            $conn = new ConnectionMock([], $driverMock, $config, $eventManager);
        }
        $config->setMetadataDriverImpl($metadataDriver);

        return EntityManagerMock::create($conn, $config, $eventManager);
    }

    /**
     * @return ClassMetadataFactoryTestSubject
     */
    protected function createTestFactory()
    {
        $mockDriver = new MetadataDriverMock();
        $entityManager = $this->createEntityManager($mockDriver);
        $cmf = new ClassMetadataFactoryTestSubject();
        $cmf->setEntityManager($entityManager);
        return $cmf;
    }

    /**
     * @param string $class
     * @return ClassMetadata
     */
    protected function createValidClassMetadata()
    {
        // Self-made metadata
        $cm1 = new ClassMetadata(TestEntity1::class);
        $cm1->initializeReflection(new RuntimeReflectionService());

        $tableMetadata = new Mapping\TableMetadata();
        $tableMetadata->setName('group');

        $cm1->setPrimaryTable($tableMetadata);

        // Add a mapped field
        $fieldMetadata = new Mapping\FieldMetadata('id');

        $fieldMetadata->setType(Type::getType('integer'));
        $fieldMetadata->setPrimaryKey(true);

        $cm1->addProperty($fieldMetadata);

        // Add a mapped field
        $fieldMetadata = new Mapping\FieldMetadata('name');

        $fieldMetadata->setType(Type::getType('string'));

        $cm1->addProperty($fieldMetadata);

        // and a mapped association
        $association = new Mapping\OneToOneAssociationMetadata('other');

        $association->setTargetEntity('TestEntity1');
        $association->setMappedBy('this');

        $cm1->addAssociation($association);

        // and an association on the owning side
        $joinColumns = [];

        $joinColumn = new Mapping\JoinColumnMetadata();

        $joinColumn->setColumnName("other_id");
        $joinColumn->setReferencedColumnName("id");

        $joinColumns[] = $joinColumn;

        $association = new Mapping\OneToOneAssociationMetadata('association');

        $association->setJoinColumns($joinColumns);
        $association->setTargetEntity('TestEntity1');

        $cm1->addAssociation($association);

        // and an id generator type
        $cm1->setIdGeneratorType(Mapping\GeneratorType::AUTO);

        return $cm1;
    }

    /**
    * @group DDC-1845
    */
    public function testQuoteMetadata()
    {
        $cmf    = new ClassMetadataFactory();
        $driver = $this->createAnnotationDriver([__DIR__ . '/../../Models/Quote/']);
        $em     = $this->createEntityManager($driver);
        $cmf->setEntityManager($em);

        $userMetadata       = $cmf->getMetadataFor(Quote\User::class);
        $phoneMetadata      = $cmf->getMetadataFor(Quote\Phone::class);
        $groupMetadata      = $cmf->getMetadataFor(Quote\Group::class);
        $addressMetadata    = $cmf->getMetadataFor(Quote\Address::class);

        // Phone Class Metadata
        self::assertNotNull($phoneMetadata->getProperty('number'));
        self::assertEquals('phone-number', $phoneMetadata->getProperty('number')->getColumnName());

        $user                = $phoneMetadata->associationMappings['user'];
        $userJoinColumns     = $user->getJoinColumns();
        $phoneUserJoinColumn = reset($userJoinColumns);

        self::assertEquals('user-id', $phoneUserJoinColumn->getColumnName());
        self::assertEquals('user-id', $phoneUserJoinColumn->getReferencedColumnName());

        // Address Class Metadata
        self::assertNotNull($addressMetadata->getProperty('id'));
        self::assertNotNull($addressMetadata->getProperty('zip'));
        self::assertEquals('address-id', $addressMetadata->getProperty('id')->getColumnName());
        self::assertEquals('address-zip', $addressMetadata->getProperty('zip')->getColumnName());

        // User Class Metadata
        self::assertNotNull($userMetadata->getProperty('id'));
        self::assertNotNull($userMetadata->getProperty('name'));
        self::assertEquals('user-id', $userMetadata->getProperty('id')->getColumnName());
        self::assertEquals('user-name', $userMetadata->getProperty('name')->getColumnName());

        $group               = $groupMetadata->associationMappings['parent'];
        $groupJoinColumns    = $group->getJoinColumns();
        $groupUserJoinColumn = reset($groupJoinColumns);

        self::assertEquals('parent-id', $groupUserJoinColumn->getColumnName());
        self::assertEquals('group-id', $groupUserJoinColumn->getReferencedColumnName());

        $user                  = $addressMetadata->associationMappings['user'];
        $userJoinColumns       = $user->getJoinColumns();
        $addressUserJoinColumn = reset($userJoinColumns);

        self::assertEquals('user-id', $addressUserJoinColumn->getColumnName());
        self::assertEquals('user-id', $addressUserJoinColumn->getReferencedColumnName());

        $address               = $userMetadata->associationMappings['address'];
        $addressJoinColumns    = $address->getJoinColumns();
        $userAddressJoinColumn = reset($addressJoinColumns);

        self::assertEquals('address-id', $userAddressJoinColumn->getColumnName());
        self::assertEquals('address-id', $userAddressJoinColumn->getReferencedColumnName());

        $groups                       = $userMetadata->associationMappings['groups'];
        $groupsJoinTable              = $groups->getJoinTable();
        $userGroupsJoinColumns        = $groupsJoinTable->getJoinColumns();
        $userGroupsJoinColumn         = reset($userGroupsJoinColumns);
        $userGroupsInverseJoinColumns = $groupsJoinTable->getInverseJoinColumns();
        $userGroupsInverseJoinColumn  = reset($userGroupsInverseJoinColumns);

        self::assertEquals('quote-users-groups', $groupsJoinTable->getName());
        self::assertEquals('user-id', $userGroupsJoinColumn->getColumnName());
        self::assertEquals('user-id', $userGroupsJoinColumn->getReferencedColumnName());
        self::assertEquals('group-id', $userGroupsInverseJoinColumn->getColumnName());
        self::assertEquals('group-id', $userGroupsInverseJoinColumn->getReferencedColumnName());
    }

    /**
     * @group DDC-3385
     * @group 1181
     * @group 385
     */
    public function testFallbackLoadingCausesEventTriggeringThatCanModifyFetchedMetadata()
    {
        $test          = $this;
        /* @var $metadata \Doctrine\Common\Persistence\Mapping\ClassMetadata */
        $metadata      = $this->createMock(ClassMetadata::class);
        $cmf           = new ClassMetadataFactory();
        $mockDriver    = new MetadataDriverMock();
        $em = $this->createEntityManager($mockDriver);
        $listener      = $this->getMockBuilder(\stdClass::class)->setMethods(['onClassMetadataNotFound'])->getMock();
        $eventManager  = $em->getEventManager();

        $cmf->setEntityManager($em);

        $listener
            ->expects($this->any())
            ->method('onClassMetadataNotFound')
            ->will($this->returnCallback(function (OnClassMetadataNotFoundEventArgs $args) use ($metadata, $em, $test) {
                $test->assertNull($args->getFoundMetadata());
                $test->assertSame('Foo', $args->getClassName());
                $test->assertSame($em, $args->getObjectManager());

                $args->setFoundMetadata($metadata);
            }));

        $eventManager->addEventListener([Events::onClassMetadataNotFound], $listener);

        self::assertSame($metadata, $cmf->getMetadataFor('Foo'));
    }

    /**
     * @group DDC-3427
     */
    public function testAcceptsEntityManagerInterfaceInstances()
    {
        $classMetadataFactory = new ClassMetadataFactory();

        /* @var $entityManager EntityManager */
        $entityManager        = $this->createMock(EntityManagerInterface::class);

        $classMetadataFactory->setEntityManager($entityManager);

        // not really the cleanest way to check it, but we won't add a getter to the CMF just for the sake of testing.
        self::assertAttributeSame($entityManager, 'em', $classMetadataFactory);
    }

    /**
     * @group embedded
     * @group DDC-3305
     */
    public function testRejectsEmbeddableWithoutValidClassName()
    {
        $metadata = $this->createValidClassMetadata();

        $metadata->mapEmbedded(
            [
            'fieldName'    => 'embedded',
            'class'        => '',
            'columnPrefix' => false,
            ]
        );

        $cmf = $this->createTestFactory();

        $cmf->setMetadataForClass($metadata->name, $metadata);

        $this->expectException(MappingException::class);
        $this->expectExceptionMessage('The embed mapping \'embedded\' misses the \'class\' attribute.');

        $cmf->getMetadataFor($metadata->name);
    }

    /**
     * @group embedded
     * @group DDC-4006
     */
    public function testInheritsIdGeneratorMappingFromEmbeddable()
    {
        $cmf = new ClassMetadataFactory();
        $driver = $this->createAnnotationDriver([__DIR__ . '/../../Models/DDC4006/']);
        $em = $this->createEntityManager($driver);
        $cmf->setEntityManager($em);

        $userMetadata = $cmf->getMetadataFor(DDC4006User::class);

        self::assertTrue($userMetadata->isIdGeneratorIdentity());
    }
}

/* Test subject class with overridden factory method for mocking purposes */
class ClassMetadataFactoryTestSubject extends ClassMetadataFactory
{
    private $mockMetadata = [];
    private $requestedClasses = [];

    /** @override */
    protected function newClassMetadataInstance($className)
    {
        $this->requestedClasses[] = $className;
        if ( ! isset($this->mockMetadata[$className])) {
            throw new \InvalidArgumentException("No mock metadata found for class $className.");
        }
        return $this->mockMetadata[$className];
    }

    public function setMetadataForClass($className, $metadata)
    {
        $this->mockMetadata[$className] = $metadata;
    }

    public function getRequestedClasses()
    {
        return $this->requestedClasses;
    }
}

class TestEntity1
{
    private $id;
    private $name;
    private $other;
    private $association;
    private $embedded;
}

class CustomIdGenerator implements Generator
{
    /**
     * {@inheritdoc}
     */
    public function generate(EntityManager $em, $entity)
    {
    }

    /**
     * {@inheritdoc}
     */
    public function isPostInsertGenerator()
    {
        return false;
    }
}
