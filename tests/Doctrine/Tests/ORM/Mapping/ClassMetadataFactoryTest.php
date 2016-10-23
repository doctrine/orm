<?php

namespace Doctrine\Tests\ORM\Mapping;

use Doctrine\Common\EventManager;
use Doctrine\Common\Persistence\Mapping\Driver\MappingDriver;
use Doctrine\Common\Persistence\Mapping\RuntimeReflectionService;
use Doctrine\DBAL\Connection;
use Doctrine\ORM\Configuration;
use Doctrine\DBAL\Types\Type;
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
use Doctrine\Tests\OrmTestCase;

class ClassMetadataFactoryTest extends OrmTestCase
{
    public function testGetMetadataForSingleClass()
    {
        $mockDriver = new MetadataDriverMock();
        $entityManager = $this->_createEntityManager($mockDriver);

        $conn = $entityManager->getConnection();
        $mockPlatform = $conn->getDatabasePlatform();
        $mockPlatform->setPrefersSequences(true);
        $mockPlatform->setPrefersIdentityColumns(false);

        $cm1 = $this->_createValidClassMetadata();

        // SUT
        $cmf = new ClassMetadataFactory();
        $cmf->setEntityManager($entityManager);
        $cmf->setMetadataFor($cm1->name, $cm1);

        // Prechecks
        self::assertEquals(array(), $cm1->parentClasses);
        self::assertEquals(Mapping\InheritanceType::NONE, $cm1->inheritanceType);
        self::assertEquals(Mapping\GeneratorType::AUTO, $cm1->generatorType);
        self::assertTrue($cm1->hasField('name'));
        self::assertEquals(2, count($cm1->associationMappings));
        self::assertEquals('group', $cm1->table->getName());

        // Go
        $cmMap1 = $cmf->getMetadataFor($cm1->name);

        self::assertSame($cm1, $cmMap1);
        self::assertEquals('group', $cmMap1->table->getName());
        self::assertEquals(array(), $cmMap1->parentClasses);
        self::assertTrue($cmMap1->hasField('name'));
    }

    public function testGetMetadataFor_ReturnsLoadedCustomIdGenerator()
    {
        $cm1 = $this->_createValidClassMetadata();

        $cm1->setIdGeneratorType(Mapping\GeneratorType::CUSTOM);

        $cm1->generatorDefinition = array(
            'class' => 'Doctrine\Tests\ORM\Mapping\CustomIdGenerator',
            'arguments' => [],
        );

        $cmf = $this->_createTestFactory();

        $cmf->setMetadataForClass($cm1->name, $cm1);

        $actual = $cmf->getMetadataFor($cm1->name);

        self::assertEquals(Mapping\GeneratorType::CUSTOM, $actual->generatorType);
        self::assertInstanceOf('Doctrine\Tests\ORM\Mapping\CustomIdGenerator', $actual->idGenerator);
    }

    public function testGetMetadataFor_ThrowsExceptionOnUnknownCustomGeneratorClass()
    {
        $cm1 = $this->_createValidClassMetadata();

        $cm1->setIdGeneratorType(Mapping\GeneratorType::CUSTOM);

        $cm1->generatorDefinition = array(
            'class' => 'NotExistingGenerator',
            'arguments' => [],
        );

        $cmf = $this->_createTestFactory();

        $cmf->setMetadataForClass($cm1->name, $cm1);

        $this->expectException(ORMException::class);

        $actual = $cmf->getMetadataFor($cm1->name);
    }

    public function testGetMetadataFor_ThrowsExceptionOnMissingCustomGeneratorDefinition()
    {
        $cm1 = $this->_createValidClassMetadata();
        $cm1->setIdGeneratorType(Mapping\GeneratorType::CUSTOM);
        $cmf = $this->_createTestFactory();
        $cmf->setMetadataForClass($cm1->name, $cm1);
        $this->expectException(ORMException::class);

        $actual = $cmf->getMetadataFor($cm1->name);
    }

    public function testHasGetMetadata_NamespaceSeparatorIsNotNormalized()
    {
        require_once __DIR__."/../../Models/Global/GlobalNamespaceModel.php";

        $metadataDriver = $this->createAnnotationDriver(array(__DIR__ . '/../../Models/Global/'));

        $entityManager = $this->_createEntityManager($metadataDriver);

        $mf = $entityManager->getMetadataFactory();
        $m1 = $mf->getMetadataFor("DoctrineGlobal_Article");
        $h1 = $mf->hasMetadataFor("DoctrineGlobal_Article");
        $h2 = $mf->hasMetadataFor("\DoctrineGlobal_Article");
        $m2 = $mf->getMetadataFor("\DoctrineGlobal_Article");

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
               ->with($this->equalTo('Doctrine\Tests\Models\CMS\CmsUser'))
               ->will($this->returnValue(true));
        $driver->expects($this->at(1))
               ->method('isTransient')
               ->with($this->equalTo('Doctrine\Tests\Models\CMS\CmsArticle'))
               ->will($this->returnValue(false));

        $em = $this->_createEntityManager($driver);

        self::assertTrue($em->getMetadataFactory()->isTransient('Doctrine\Tests\Models\CMS\CmsUser'));
        self::assertFalse($em->getMetadataFactory()->isTransient('Doctrine\Tests\Models\CMS\CmsArticle'));
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
               ->with($this->equalTo('Doctrine\Tests\Models\CMS\CmsUser'))
               ->will($this->returnValue(true));
        $driver->expects($this->at(1))
               ->method('isTransient')
               ->with($this->equalTo('Doctrine\Tests\Models\CMS\CmsArticle'))
               ->will($this->returnValue(false));

        $em = $this->_createEntityManager($driver);
        $em->getConfiguration()->addEntityNamespace('CMS', 'Doctrine\Tests\Models\CMS');

        self::assertTrue($em->getMetadataFactory()->isTransient('CMS:CmsUser'));
        self::assertFalse($em->getMetadataFactory()->isTransient('CMS:CmsArticle'));
    }

    public function testAddDefaultDiscriminatorMap()
    {
        $cmf = new ClassMetadataFactory();
        $driver = $this->createAnnotationDriver(array(__DIR__ . '/../../Models/JoinedInheritanceType/'));
        $em = $this->_createEntityManager($driver);
        $cmf->setEntityManager($em);

        $rootMetadata = $cmf->getMetadataFor('Doctrine\Tests\Models\JoinedInheritanceType\RootClass');
        $childMetadata = $cmf->getMetadataFor('Doctrine\Tests\Models\JoinedInheritanceType\ChildClass');
        $anotherChildMetadata = $cmf->getMetadataFor('Doctrine\Tests\Models\JoinedInheritanceType\AnotherChildClass');
        $rootDiscriminatorMap = $rootMetadata->discriminatorMap;
        $childDiscriminatorMap = $childMetadata->discriminatorMap;
        $anotherChildDiscriminatorMap = $anotherChildMetadata->discriminatorMap;

        $rootClass = 'Doctrine\Tests\Models\JoinedInheritanceType\RootClass';
        $childClass = 'Doctrine\Tests\Models\JoinedInheritanceType\ChildClass';
        $anotherChildClass = 'Doctrine\Tests\Models\JoinedInheritanceType\AnotherChildClass';

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
        $cmf = $this->getMockBuilder(ClassMetadataFactory::class)->setMethods(array('addDefaultDiscriminatorMap'))->getMock();
        $cmf->setEntityManager($em);
        $cmf->expects($this->never())
            ->method('addDefaultDiscriminatorMap');

        $rootMetadata = $cmf->getMetadataFor('Doctrine\Tests\Models\JoinedInheritanceType\RootClass');
    }

    public function testGetAllMetadataWorksWithBadConnection()
    {
        // DDC-3551
        $conn = $this->createMock(Connection::class);
        $mockDriver    = new MetadataDriverMock();
        $em = $this->_createEntityManager($mockDriver, $conn);

        $conn->expects($this->any())
            ->method('getDatabasePlatform')
            ->will($this->throwException(new \Exception('Exception thrown in test when calling getDatabasePlatform')));

        $cmf = new ClassMetadataFactory();
        $cmf->setEntityManager($em);

        // getting all the metadata should work, even if get DatabasePlatform blows up
        $metadata = $cmf->getAllMetadata();
        // this will just be an empty array - there was no error
        self::assertEquals(array(), $metadata);
    }

    protected function _createEntityManager($metadataDriver, $conn = null)
    {
        $driverMock = new DriverMock();
        $config = new Configuration();
        $config->setProxyDir(__DIR__ . '/../../Proxies');
        $config->setProxyNamespace('Doctrine\Tests\Proxies');
        $eventManager = new EventManager();
        if (!$conn) {
            $conn = new ConnectionMock(array(), $driverMock, $config, $eventManager);
        }
        $config->setMetadataDriverImpl($metadataDriver);

        return EntityManagerMock::create($conn, $config, $eventManager);
    }

    /**
     * @return ClassMetadataFactoryTestSubject
     */
    protected function _createTestFactory()
    {
        $mockDriver = new MetadataDriverMock();
        $entityManager = $this->_createEntityManager($mockDriver);
        $cmf = new ClassMetadataFactoryTestSubject();
        $cmf->setEntityManager($entityManager);
        return $cmf;
    }

    /**
     * @param string $class
     * @return ClassMetadata
     */
    protected function _createValidClassMetadata()
    {
        // Self-made metadata
        $cm1 = new ClassMetadata('Doctrine\Tests\ORM\Mapping\TestEntity1');

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
        $cm1->mapOneToOne(array(
            'fieldName'    => 'other',
            'targetEntity' => 'TestEntity1',
            'mappedBy'     => 'this'
        ));

        // and an association on the owning side
        $joinColumns = array();

        $joinColumn = new Mapping\JoinColumnMetadata();

        $joinColumn->setColumnName("other_id");
        $joinColumn->setReferencedColumnName("id");

        $joinColumns[] = $joinColumn;

        $cm1->mapOneToOne(array(
            'fieldName'    => 'association',
            'targetEntity' => 'TestEntity1',
            'joinColumns'  => $joinColumns,
        ));

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
        $driver = $this->createAnnotationDriver(array(__DIR__ . '/../../Models/Quote/'));
        $em     = $this->_createEntityManager($driver);
        $cmf->setEntityManager($em);

        $userMetadata       = $cmf->getMetadataFor('Doctrine\Tests\Models\Quote\User');
        $phoneMetadata      = $cmf->getMetadataFor('Doctrine\Tests\Models\Quote\Phone');
        $groupMetadata      = $cmf->getMetadataFor('Doctrine\Tests\Models\Quote\Group');
        $addressMetadata    = $cmf->getMetadataFor('Doctrine\Tests\Models\Quote\Address');

        // Phone Class Metadata
        self::assertNotNull($phoneMetadata->getProperty('number'));
        self::assertEquals('phone-number', $phoneMetadata->getProperty('number')->getColumnName());

        $user                = $phoneMetadata->associationMappings['user'];
        $phoneUserJoinColumn = reset($user['joinColumns']);

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
        $groupUserJoinColumn = reset($group['joinColumns']);

        self::assertEquals('parent-id', $groupUserJoinColumn->getColumnName());
        self::assertEquals('group-id', $groupUserJoinColumn->getReferencedColumnName());

        $user                  = $addressMetadata->associationMappings['user'];
        $addressUserJoinColumn = reset($user['joinColumns']);

        self::assertEquals('user-id', $addressUserJoinColumn->getColumnName());
        self::assertEquals('user-id', $addressUserJoinColumn->getReferencedColumnName());

        $address               = $userMetadata->associationMappings['address'];
        $userAddressJoinColumn = reset($address['joinColumns']);

        self::assertEquals('address-id', $userAddressJoinColumn->getColumnName());
        self::assertEquals('address-id', $userAddressJoinColumn->getReferencedColumnName());

        $groups                       = $userMetadata->associationMappings['groups'];
        $userGroupsJoinColumns        = $groups['joinTable']->getJoinColumns();
        $userGroupsJoinColumn         = reset($userGroupsJoinColumns);
        $userGroupsInverseJoinColumns = $groups['joinTable']->getInverseJoinColumns();
        $userGroupsInverseJoinColumn  = reset($userGroupsInverseJoinColumns);

        self::assertEquals('quote-users-groups', $groups['joinTable']->getName());
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
        $em = $this->_createEntityManager($mockDriver);
        $listener      = $this->getMockBuilder(\stdClass::class)->setMethods(array('onClassMetadataNotFound'))->getMock();
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

        $eventManager->addEventListener(array(Events::onClassMetadataNotFound), $listener);

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
        $metadata = $this->_createValidClassMetadata();

        $metadata->mapEmbedded(array(
            'fieldName'    => 'embedded',
            'class'        => '',
            'columnPrefix' => false,
        ));

        $cmf = $this->_createTestFactory();

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
        $driver = $this->createAnnotationDriver(array(__DIR__ . '/../../Models/DDC4006/'));
        $em = $this->_createEntityManager($driver);
        $cmf->setEntityManager($em);

        $userMetadata = $cmf->getMetadataFor('Doctrine\Tests\Models\DDC4006\DDC4006User');

        self::assertTrue($userMetadata->isIdGeneratorIdentity());
    }
}

/* Test subject class with overridden factory method for mocking purposes */
class ClassMetadataFactoryTestSubject extends ClassMetadataFactory
{
    private $mockMetadata = array();
    private $requestedClasses = array();

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
