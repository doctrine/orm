<?php

namespace Doctrine\Tests\ORM\Mapping;

use Doctrine\Common\EventManager;
use Doctrine\Common\Persistence\Mapping\Driver\MappingDriver;
use Doctrine\Common\Persistence\Mapping\RuntimeReflectionService;
use Doctrine\DBAL\Connection;
use Doctrine\ORM\Configuration;
use Doctrine\ORM\EntityManager;
use Doctrine\ORM\EntityManagerInterface;
use Doctrine\ORM\Event\OnClassMetadataNotFoundEventArgs;
use Doctrine\ORM\Events;
use Doctrine\ORM\Id\AbstractIdGenerator;
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
        $this->assertEquals([], $cm1->parentClasses);
        $this->assertEquals(ClassMetadata::INHERITANCE_TYPE_NONE, $cm1->inheritanceType);
        $this->assertTrue($cm1->hasField('name'));
        $this->assertEquals(2, count($cm1->associationMappings));
        $this->assertEquals(ClassMetadata::GENERATOR_TYPE_AUTO, $cm1->generatorType);
        $this->assertEquals('group', $cm1->table['name']);

        // Go
        $cmMap1 = $cmf->getMetadataFor($cm1->name);

        $this->assertSame($cm1, $cmMap1);
        $this->assertEquals('group', $cmMap1->table['name']);
        $this->assertTrue($cmMap1->table['quoted']);
        $this->assertEquals([], $cmMap1->parentClasses);
        $this->assertTrue($cmMap1->hasField('name'));
    }

    public function testGetMetadataFor_ReturnsLoadedCustomIdGenerator()
    {
        $cm1 = $this->_createValidClassMetadata();
        $cm1->setIdGeneratorType(ClassMetadata::GENERATOR_TYPE_CUSTOM);
        $cm1->customGeneratorDefinition = ['class' => CustomIdGenerator::class];
        $cmf = $this->_createTestFactory();
        $cmf->setMetadataForClass($cm1->name, $cm1);

        $actual = $cmf->getMetadataFor($cm1->name);

        $this->assertEquals(ClassMetadata::GENERATOR_TYPE_CUSTOM, $actual->generatorType);
        $this->assertInstanceOf(CustomIdGenerator::class, $actual->idGenerator);
    }

    public function testGetMetadataFor_ThrowsExceptionOnUnknownCustomGeneratorClass()
    {
        $cm1 = $this->_createValidClassMetadata();
        $cm1->setIdGeneratorType(ClassMetadata::GENERATOR_TYPE_CUSTOM);
        $cm1->customGeneratorDefinition = ["class" => "NotExistingGenerator"];
        $cmf = $this->_createTestFactory();
        $cmf->setMetadataForClass($cm1->name, $cm1);
        $this->expectException(ORMException::class);

        $actual = $cmf->getMetadataFor($cm1->name);
    }

    public function testGetMetadataFor_ThrowsExceptionOnMissingCustomGeneratorDefinition()
    {
        $cm1 = $this->_createValidClassMetadata();
        $cm1->setIdGeneratorType(ClassMetadata::GENERATOR_TYPE_CUSTOM);
        $cmf = $this->_createTestFactory();
        $cmf->setMetadataForClass($cm1->name, $cm1);
        $this->expectException(ORMException::class);

        $actual = $cmf->getMetadataFor($cm1->name);
    }

    public function testHasGetMetadata_NamespaceSeparatorIsNotNormalized()
    {
        require_once __DIR__."/../../Models/Global/GlobalNamespaceModel.php";

        $metadataDriver = $this->createAnnotationDriver([__DIR__ . '/../../Models/Global/']);

        $entityManager = $this->_createEntityManager($metadataDriver);

        $mf = $entityManager->getMetadataFactory();
        $m1 = $mf->getMetadataFor(DoctrineGlobal_Article::class);
        $h1 = $mf->hasMetadataFor(DoctrineGlobal_Article::class);
        $h2 = $mf->hasMetadataFor('\\' . DoctrineGlobal_Article::class);
        $m2 = $mf->getMetadataFor('\\' . DoctrineGlobal_Article::class);

        $this->assertNotSame($m1, $m2);
        $this->assertFalse($h2);
        $this->assertTrue($h1);
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

        $em = $this->_createEntityManager($driver);

        $this->assertTrue($em->getMetadataFactory()->isTransient(CmsUser::class));
        $this->assertFalse($em->getMetadataFactory()->isTransient(CmsArticle::class));
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

        $em = $this->_createEntityManager($driver);
        $em->getConfiguration()->addEntityNamespace('CMS', 'Doctrine\Tests\Models\CMS');

        $this->assertTrue($em->getMetadataFactory()->isTransient('CMS:CmsUser'));
        $this->assertFalse($em->getMetadataFactory()->isTransient('CMS:CmsArticle'));
    }

    public function testAddDefaultDiscriminatorMap()
    {
        self::markTestSkipped('This test is just incorrect and must be fixed');

        $cmf = new ClassMetadataFactory();
        $driver = $this->createAnnotationDriver([__DIR__ . '/../../Models/JoinedInheritanceType/']);
        $em = $this->_createEntityManager($driver);
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

        $this->assertEquals('rootclass', $rootClassKey);
        $this->assertEquals('childclass', $childClassKey);
        $this->assertEquals('anotherchildclass', $anotherChildClassKey);

        $this->assertEquals($childDiscriminatorMap, $rootDiscriminatorMap);
        $this->assertEquals($anotherChildDiscriminatorMap, $rootDiscriminatorMap);

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
        $em = $this->_createEntityManager($mockDriver, $conn);

        $conn->expects($this->any())
            ->method('getDatabasePlatform')
            ->will($this->throwException(new \Exception('Exception thrown in test when calling getDatabasePlatform')));

        $cmf = new ClassMetadataFactory();
        $cmf->setEntityManager($em);

        // getting all the metadata should work, even if get DatabasePlatform blows up
        $metadata = $cmf->getAllMetadata();
        // this will just be an empty array - there was no error
        $this->assertEquals([], $metadata);
    }

    protected function _createEntityManager($metadataDriver, $conn = null)
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
        $cm1 = new ClassMetadata(TestEntity1::class);
        $cm1->initializeReflection(new RuntimeReflectionService());
        $cm1->setPrimaryTable(['name' => '`group`']);
        // Add a mapped field
        $cm1->mapField(['fieldName' => 'name', 'type' => 'string']);
        // Add a mapped field
        $cm1->mapField(['fieldName' => 'id', 'type' => 'integer', 'id' => true]);
        // and a mapped association
        $cm1->mapOneToOne(['fieldName' => 'other', 'targetEntity' => 'TestEntity1', 'mappedBy' => 'this']);
        // and an association on the owning side
        $joinColumns = [
            ['name' => 'other_id', 'referencedColumnName' => 'id']
        ];
        $cm1->mapOneToOne(
            ['fieldName' => 'association', 'targetEntity' => 'TestEntity1', 'joinColumns' => $joinColumns]
        );
        // and an id generator type
        $cm1->setIdGeneratorType(ClassMetadata::GENERATOR_TYPE_AUTO);
        return $cm1;
    }

    /**
    * @group DDC-1845
    */
    public function testQuoteMetadata()
    {
        $cmf    = new ClassMetadataFactory();
        $driver = $this->createAnnotationDriver([__DIR__ . '/../../Models/Quote/']);
        $em     = $this->_createEntityManager($driver);
        $cmf->setEntityManager($em);


        $userMetadata       = $cmf->getMetadataFor(Quote\User::class);
        $phoneMetadata      = $cmf->getMetadataFor(Quote\Phone::class);
        $groupMetadata      = $cmf->getMetadataFor(Quote\Group::class);
        $addressMetadata    = $cmf->getMetadataFor(Quote\Address::class);


        // Phone Class Metadata
        $this->assertTrue($phoneMetadata->fieldMappings['number']['quoted']);
        $this->assertEquals('phone-number', $phoneMetadata->fieldMappings['number']['columnName']);

        $user = $phoneMetadata->associationMappings['user'];
        $this->assertTrue($user['joinColumns'][0]['quoted']);
        $this->assertEquals('user-id', $user['joinColumns'][0]['name']);
        $this->assertEquals('user-id', $user['joinColumns'][0]['referencedColumnName']);



        // User Group Metadata
        $this->assertTrue($groupMetadata->fieldMappings['id']['quoted']);
        $this->assertTrue($groupMetadata->fieldMappings['name']['quoted']);

        $this->assertEquals('user-id', $userMetadata->fieldMappings['id']['columnName']);
        $this->assertEquals('user-name', $userMetadata->fieldMappings['name']['columnName']);

        $user = $groupMetadata->associationMappings['parent'];
        $this->assertTrue($user['joinColumns'][0]['quoted']);
        $this->assertEquals('parent-id', $user['joinColumns'][0]['name']);
        $this->assertEquals('group-id', $user['joinColumns'][0]['referencedColumnName']);


        // Address Class Metadata
        $this->assertTrue($addressMetadata->fieldMappings['id']['quoted']);
        $this->assertTrue($addressMetadata->fieldMappings['zip']['quoted']);

        $this->assertEquals('address-id', $addressMetadata->fieldMappings['id']['columnName']);
        $this->assertEquals('address-zip', $addressMetadata->fieldMappings['zip']['columnName']);

        $user = $addressMetadata->associationMappings['user'];
        $this->assertTrue($user['joinColumns'][0]['quoted']);
        $this->assertEquals('user-id', $user['joinColumns'][0]['name']);
        $this->assertEquals('user-id', $user['joinColumns'][0]['referencedColumnName']);



        // User Class Metadata
        $this->assertTrue($userMetadata->fieldMappings['id']['quoted']);
        $this->assertTrue($userMetadata->fieldMappings['name']['quoted']);

        $this->assertEquals('user-id', $userMetadata->fieldMappings['id']['columnName']);
        $this->assertEquals('user-name', $userMetadata->fieldMappings['name']['columnName']);


        $address = $userMetadata->associationMappings['address'];
        $this->assertTrue($address['joinColumns'][0]['quoted']);
        $this->assertEquals('address-id', $address['joinColumns'][0]['name']);
        $this->assertEquals('address-id', $address['joinColumns'][0]['referencedColumnName']);

        $groups = $userMetadata->associationMappings['groups'];
        $this->assertTrue($groups['joinTable']['quoted']);
        $this->assertTrue($groups['joinTable']['joinColumns'][0]['quoted']);
        $this->assertEquals('quote-users-groups', $groups['joinTable']['name']);
        $this->assertEquals('user-id', $groups['joinTable']['joinColumns'][0]['name']);
        $this->assertEquals('user-id', $groups['joinTable']['joinColumns'][0]['referencedColumnName']);

        $this->assertTrue($groups['joinTable']['inverseJoinColumns'][0]['quoted']);
        $this->assertEquals('group-id', $groups['joinTable']['inverseJoinColumns'][0]['name']);
        $this->assertEquals('group-id', $groups['joinTable']['inverseJoinColumns'][0]['referencedColumnName']);
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

        $this->assertSame($metadata, $cmf->getMetadataFor('Foo'));
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
        $this->assertAttributeSame($entityManager, 'em', $classMetadataFactory);
    }

    /**
     * @group DDC-3305
     */
    public function testRejectsEmbeddableWithoutValidClassName()
    {
        $metadata = $this->_createValidClassMetadata();

        $metadata->mapEmbedded(
            [
            'fieldName'    => 'embedded',
            'class'        => '',
            'columnPrefix' => false,
            ]
        );

        $cmf = $this->_createTestFactory();

        $cmf->setMetadataForClass($metadata->name, $metadata);

        $this->expectException(MappingException::class);
        $this->expectExceptionMessage('The embed mapping \'embedded\' misses the \'class\' attribute.');

        $cmf->getMetadataFor($metadata->name);
    }

    /**
     * @group DDC-4006
     */
    public function testInheritsIdGeneratorMappingFromEmbeddable()
    {
        $cmf = new ClassMetadataFactory();
        $driver = $this->createAnnotationDriver([__DIR__ . '/../../Models/DDC4006/']);
        $em = $this->_createEntityManager($driver);
        $cmf->setEntityManager($em);

        $userMetadata = $cmf->getMetadataFor(DDC4006User::class);

        $this->assertTrue($userMetadata->isIdGeneratorIdentity());
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

class CustomIdGenerator extends AbstractIdGenerator
{
    public function generate(EntityManager $em, $entity)
    {
    }
}
