<?php

declare(strict_types=1);

namespace Doctrine\Tests\ORM\Mapping;

use Doctrine\Common\EventManager;
use Doctrine\Common\Persistence\PersistentObject;
use Doctrine\DBAL\Connection;
use Doctrine\DBAL\Driver;
use Doctrine\DBAL\Platforms\AbstractPlatform;
use Doctrine\Deprecations\PHPUnit\VerifyDeprecations;
use Doctrine\ORM\Configuration;
use Doctrine\ORM\EntityManagerInterface;
use Doctrine\ORM\Event\OnClassMetadataNotFoundEventArgs;
use Doctrine\ORM\Events;
use Doctrine\ORM\Exception\ORMException;
use Doctrine\ORM\Id\AbstractIdGenerator;
use Doctrine\ORM\Mapping\ClassMetadata;
use Doctrine\ORM\Mapping\ClassMetadataFactory;
use Doctrine\ORM\Mapping\Column;
use Doctrine\ORM\Mapping\DiscriminatorColumn;
use Doctrine\ORM\Mapping\DiscriminatorMap;
use Doctrine\ORM\Mapping\Entity;
use Doctrine\ORM\Mapping\Exception\CannotGenerateIds;
use Doctrine\ORM\Mapping\GeneratedValue;
use Doctrine\ORM\Mapping\Id;
use Doctrine\ORM\Mapping\InheritanceType;
use Doctrine\ORM\Mapping\MappingException;
use Doctrine\Persistence\Mapping\Driver\MappingDriver;
use Doctrine\Persistence\Mapping\RuntimeReflectionService;
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
use Doctrine\Tests\PHPUnitCompatibility\MockBuilderCompatibilityTools;
use Doctrine\Tests\TestUtil;
use Exception;
use InvalidArgumentException;
use PHPUnit\Framework\Assert;
use ReflectionClass;

use function array_search;
use function assert;
use function class_exists;
use function count;
use function sprintf;

class ClassMetadataFactoryTest extends OrmTestCase
{
    use MockBuilderCompatibilityTools;
    use VerifyDeprecations;

    public function testGetMetadataForSingleClass(): void
    {
        $platform = $this->createMock(AbstractPlatform::class);
        $platform->method('supportsSequences')
            ->willReturn(true);

        $driver = $this->createMock(Driver::class);
        $driver->method('getDatabasePlatform')
            ->willReturn($platform);

        $conn = new Connection([], $driver);

        $mockDriver    = new MetadataDriverMock();
        $entityManager = $this->createEntityManager($mockDriver, $conn);

        $cm1 = $this->createValidClassMetadata();

        // SUT
        $cmf = new ClassMetadataFactory();
        $cmf->setEntityManager($entityManager);
        $cmf->setMetadataFor($cm1->name, $cm1);

        // Prechecks
        self::assertEquals([], $cm1->parentClasses);
        self::assertEquals(ClassMetadata::INHERITANCE_TYPE_NONE, $cm1->inheritanceType);
        self::assertTrue($cm1->hasField('name'));
        self::assertEquals(2, count($cm1->associationMappings));
        self::assertEquals(ClassMetadata::GENERATOR_TYPE_AUTO, $cm1->generatorType);
        self::assertEquals('group', $cm1->table['name']);

        // Go
        $cmMap1 = $cmf->getMetadataFor($cm1->name);

        self::assertSame($cm1, $cmMap1);
        self::assertEquals('group', $cmMap1->table['name']);
        self::assertTrue($cmMap1->table['quoted']);
        self::assertEquals([], $cmMap1->parentClasses);
        self::assertTrue($cmMap1->hasField('name'));
    }

    public function testUsingIdentityWithAPlatformThatDoesNotSupportIdentityColumnsIsDeprecated(): void
    {
        $cm = $this->createValidClassMetadata();
        $cm->setIdGeneratorType(ClassMetadata::GENERATOR_TYPE_IDENTITY);
        $cmf      = new ClassMetadataFactoryTestSubject();
        $driver   = $this->createMock(Driver::class);
        $platform = $this->createStub(AbstractPlatform::class);
        $platform->method('usesSequenceEmulatedIdentityColumns')->willReturn(true);
        $platform->method('getIdentitySequenceName')->willReturn('whatever');
        $driver->method('getDatabasePlatform')->willReturn($platform);
        $entityManager = $this->createEntityManager(
            new MetadataDriverMock(),
            new Connection([], $driver)
        );
        $cmf->setEntityManager($entityManager);
        $cmf->setMetadataForClass($cm->name, $cm);

        $this->expectDeprecationWithIdentifier('https://github.com/doctrine/orm/issues/8850');
        $cmf->getMetadataFor($cm->name);
    }

    public function testItThrowsWhenUsingAutoWithIncompatiblePlatform(): void
    {
        $cm1 = $this->createValidClassMetadata();
        $cm1->setIdGeneratorType(ClassMetadata::GENERATOR_TYPE_AUTO);

        $driver = $this->createMock(Driver::class);
        $driver->method('getDatabasePlatform')
            ->willReturn($this->createMock(AbstractPlatform::class));

        $connection    = new Connection([], $driver);
        $entityManager = $this->createEntityManager(new MetadataDriverMock(), $connection);
        $cmf           = new ClassMetadataFactoryTestSubject();
        $cmf->setEntityManager($entityManager);
        $cmf->setMetadataForClass($cm1->name, $cm1);
        $this->expectException(CannotGenerateIds::class);

        $actual = $cmf->getMetadataFor($cm1->name);
    }

    public function testGetMetadataForReturnsLoadedCustomIdGenerator(): void
    {
        $cm1 = $this->createValidClassMetadata();
        $cm1->setIdGeneratorType(ClassMetadata::GENERATOR_TYPE_CUSTOM);
        $cm1->customGeneratorDefinition = ['class' => CustomIdGenerator::class];
        $cmf                            = $this->createTestFactory();
        $cmf->setMetadataForClass($cm1->name, $cm1);

        $actual = $cmf->getMetadataFor($cm1->name);

        self::assertEquals(ClassMetadata::GENERATOR_TYPE_CUSTOM, $actual->generatorType);
        self::assertInstanceOf(CustomIdGenerator::class, $actual->idGenerator);
    }

    public function testGetMetadataForThrowsExceptionOnUnknownCustomGeneratorClass(): void
    {
        $cm1 = $this->createValidClassMetadata();
        $cm1->setIdGeneratorType(ClassMetadata::GENERATOR_TYPE_CUSTOM);
        $cm1->customGeneratorDefinition = ['class' => 'NotExistingGenerator'];
        $cmf                            = $this->createTestFactory();
        $cmf->setMetadataForClass($cm1->name, $cm1);
        $this->expectException(ORMException::class);

        $actual = $cmf->getMetadataFor($cm1->name);
    }

    public function testGetMetadataForThrowsExceptionOnMissingCustomGeneratorDefinition(): void
    {
        $cm1 = $this->createValidClassMetadata();
        $cm1->setIdGeneratorType(ClassMetadata::GENERATOR_TYPE_CUSTOM);
        $cmf = $this->createTestFactory();
        $cmf->setMetadataForClass($cm1->name, $cm1);
        $this->expectException(ORMException::class);

        $actual = $cmf->getMetadataFor($cm1->name);
    }

    /** @group DDC-1512 */
    public function testIsTransient(): void
    {
        $cmf    = new ClassMetadataFactory();
        $driver = $this->createMock(MappingDriver::class);
        $driver->expects(self::exactly(2))
            ->method('isTransient')
            ->willReturnMap([
                [CmsUser::class, true],
                [CmsArticle::class, false],
            ]);

        $em = $this->createEntityManager($driver);

        self::assertTrue($em->getMetadataFactory()->isTransient(CmsUser::class));
        self::assertFalse($em->getMetadataFactory()->isTransient(CmsArticle::class));
    }

    /** @group DDC-1512 */
    public function testIsTransientEntityNamespace(): void
    {
        if (! class_exists(PersistentObject::class)) {
            $this->markTestSkipped('This test requires doctrine/persistence 2');
        }

        $this->expectDeprecationWithIdentifier('https://github.com/doctrine/orm/issues/8818');

        $cmf    = new ClassMetadataFactory();
        $driver = $this->createMock(MappingDriver::class);
        $driver->expects(self::exactly(2))
            ->method('isTransient')
            ->withConsecutive(
                [CmsUser::class],
                [CmsArticle::class]
            )
            ->willReturnMap(
                [
                    [CmsUser::class, true],
                    [CmsArticle::class, false],
                ]
            );

        $em = $this->createEntityManager($driver);
        $em->getConfiguration()->addEntityNamespace('CMS', 'Doctrine\Tests\Models\CMS');

        self::assertTrue($em->getMetadataFactory()->isTransient('CMS:CmsUser'));
        self::assertFalse($em->getMetadataFactory()->isTransient('CMS:CmsArticle'));
    }

    public function testAddDefaultDiscriminatorMap(): void
    {
        self::markTestSkipped('This test is just incorrect and must be fixed');

        $cmf    = new ClassMetadataFactory();
        $driver = $this->createAnnotationDriver([__DIR__ . '/../../Models/JoinedInheritanceType/']);
        $em     = $this->createEntityManager($driver);
        $cmf->setEntityManager($em);

        $rootMetadata                 = $cmf->getMetadataFor(RootClass::class);
        $childMetadata                = $cmf->getMetadataFor(ChildClass::class);
        $anotherChildMetadata         = $cmf->getMetadataFor(AnotherChildClass::class);
        $rootDiscriminatorMap         = $rootMetadata->discriminatorMap;
        $childDiscriminatorMap        = $childMetadata->discriminatorMap;
        $anotherChildDiscriminatorMap = $anotherChildMetadata->discriminatorMap;

        $rootClass         = RootClass::class;
        $childClass        = ChildClass::class;
        $anotherChildClass = AnotherChildClass::class;

        $rootClassKey         = array_search($rootClass, $rootDiscriminatorMap, true);
        $childClassKey        = array_search($childClass, $rootDiscriminatorMap, true);
        $anotherChildClassKey = array_search($anotherChildClass, $rootDiscriminatorMap, true);

        self::assertEquals('rootclass', $rootClassKey);
        self::assertEquals('childclass', $childClassKey);
        self::assertEquals('anotherchildclass', $anotherChildClassKey);

        self::assertEquals($childDiscriminatorMap, $rootDiscriminatorMap);
        self::assertEquals($anotherChildDiscriminatorMap, $rootDiscriminatorMap);
    }

    public function testGetAllMetadataWorksWithBadConnection(): void
    {
        // DDC-3551
        $conn       = $this->createMock(Connection::class);
        $mockDriver = new MetadataDriverMock();
        $em         = $this->createEntityManager($mockDriver, $conn);

        $conn->expects(self::any())
            ->method('getDatabasePlatform')
            ->will(self::throwException(new Exception('Exception thrown in test when calling getDatabasePlatform')));

        $cmf = new ClassMetadataFactory();
        $cmf->setEntityManager($em);

        // getting all the metadata should work, even if get DatabasePlatform blows up
        $metadata = $cmf->getAllMetadata();
        // this will just be an empty array - there was no error
        self::assertEquals([], $metadata);
    }

    protected function createEntityManager(MappingDriver $metadataDriver, $conn = null): EntityManagerMock
    {
        $config = new Configuration();
        TestUtil::configureProxies($config);
        $eventManager = new EventManager();
        if (! $conn) {
            $platform = $this->createMock(AbstractPlatform::class);
            $platform->method('supportsIdentityColumns')
                ->willReturn(true);

            $driver = $this->createMock(Driver::class);
            $driver->method('getDatabasePlatform')
                ->willReturn($platform);

            $conn = new Connection([], $driver, $config, $eventManager);
        }

        $config->setMetadataDriverImpl($metadataDriver);

        return new EntityManagerMock($conn, $config);
    }

    protected function createTestFactory(): ClassMetadataFactoryTestSubject
    {
        $mockDriver    = new MetadataDriverMock();
        $entityManager = $this->createEntityManager($mockDriver);
        $cmf           = new ClassMetadataFactoryTestSubject();
        $cmf->setEntityManager($entityManager);

        return $cmf;
    }

    protected function createValidClassMetadata(): ClassMetadata
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
            ['name' => 'other_id', 'referencedColumnName' => 'id'],
        ];
        $cm1->mapOneToOne(
            ['fieldName' => 'association', 'targetEntity' => 'TestEntity1', 'joinColumns' => $joinColumns]
        );
        // and an id generator type
        $cm1->setIdGeneratorType(ClassMetadata::GENERATOR_TYPE_AUTO);

        return $cm1;
    }

    /** @group DDC-1845 */
    public function testQuoteMetadata(): void
    {
        $cmf    = new ClassMetadataFactory();
        $driver = $this->createAnnotationDriver([__DIR__ . '/../../Models/Quote/']);
        $em     = $this->createEntityManager($driver);
        $cmf->setEntityManager($em);

        $userMetadata    = $cmf->getMetadataFor(Quote\User::class);
        $phoneMetadata   = $cmf->getMetadataFor(Quote\Phone::class);
        $groupMetadata   = $cmf->getMetadataFor(Quote\Group::class);
        $addressMetadata = $cmf->getMetadataFor(Quote\Address::class);

        // Phone Class Metadata
        self::assertTrue($phoneMetadata->fieldMappings['number']['quoted']);
        self::assertEquals('phone-number', $phoneMetadata->fieldMappings['number']['columnName']);

        $user = $phoneMetadata->associationMappings['user'];
        self::assertTrue($user['joinColumns'][0]['quoted']);
        self::assertEquals('user-id', $user['joinColumns'][0]['name']);
        self::assertEquals('user-id', $user['joinColumns'][0]['referencedColumnName']);

        // User Group Metadata
        self::assertTrue($groupMetadata->fieldMappings['id']['quoted']);
        self::assertTrue($groupMetadata->fieldMappings['name']['quoted']);

        self::assertEquals('user-id', $userMetadata->fieldMappings['id']['columnName']);
        self::assertEquals('user-name', $userMetadata->fieldMappings['name']['columnName']);

        $user = $groupMetadata->associationMappings['parent'];
        self::assertTrue($user['joinColumns'][0]['quoted']);
        self::assertEquals('parent-id', $user['joinColumns'][0]['name']);
        self::assertEquals('group-id', $user['joinColumns'][0]['referencedColumnName']);

        // Address Class Metadata
        self::assertTrue($addressMetadata->fieldMappings['id']['quoted']);
        self::assertTrue($addressMetadata->fieldMappings['zip']['quoted']);

        self::assertEquals('address-id', $addressMetadata->fieldMappings['id']['columnName']);
        self::assertEquals('address-zip', $addressMetadata->fieldMappings['zip']['columnName']);

        $user = $addressMetadata->associationMappings['user'];
        self::assertTrue($user['joinColumns'][0]['quoted']);
        self::assertEquals('user-id', $user['joinColumns'][0]['name']);
        self::assertEquals('user-id', $user['joinColumns'][0]['referencedColumnName']);

        // User Class Metadata
        self::assertTrue($userMetadata->fieldMappings['id']['quoted']);
        self::assertTrue($userMetadata->fieldMappings['name']['quoted']);

        self::assertEquals('user-id', $userMetadata->fieldMappings['id']['columnName']);
        self::assertEquals('user-name', $userMetadata->fieldMappings['name']['columnName']);

        $address = $userMetadata->associationMappings['address'];
        self::assertTrue($address['joinColumns'][0]['quoted']);
        self::assertEquals('address-id', $address['joinColumns'][0]['name']);
        self::assertEquals('address-id', $address['joinColumns'][0]['referencedColumnName']);

        $groups = $userMetadata->associationMappings['groups'];
        self::assertTrue($groups['joinTable']['quoted']);
        self::assertTrue($groups['joinTable']['joinColumns'][0]['quoted']);
        self::assertEquals('quote-users-groups', $groups['joinTable']['name']);
        self::assertEquals('user-id', $groups['joinTable']['joinColumns'][0]['name']);
        self::assertEquals('user-id', $groups['joinTable']['joinColumns'][0]['referencedColumnName']);

        self::assertTrue($groups['joinTable']['inverseJoinColumns'][0]['quoted']);
        self::assertEquals('group-id', $groups['joinTable']['inverseJoinColumns'][0]['name']);
        self::assertEquals('group-id', $groups['joinTable']['inverseJoinColumns'][0]['referencedColumnName']);
    }

    /**
     * @group DDC-3385
     * @group 1181
     * @group 385
     */
    public function testFallbackLoadingCausesEventTriggeringThatCanModifyFetchedMetadata(): void
    {
        $metadata = $this->createMock(ClassMetadata::class);
        assert($metadata instanceof ClassMetadata);
        $cmf          = new ClassMetadataFactory();
        $mockDriver   = new MetadataDriverMock();
        $em           = $this->createEntityManager($mockDriver);
        $eventManager = $em->getEventManager();

        $cmf->setEntityManager($em);

        $eventManager->addEventListener([Events::onClassMetadataNotFound], new class ($metadata, $em) {
            /** @var ClassMetadata */
            private $metadata;
            /** @var EntityManagerInterface */
            private $em;

            public function __construct(ClassMetadata $metadata, EntityManagerInterface $em)
            {
                $this->metadata = $metadata;
                $this->em       = $em;
            }

            public function onClassMetadataNotFound(OnClassMetadataNotFoundEventArgs $args): void
            {
                Assert::assertNull($args->getFoundMetadata());
                Assert::assertSame('Foo', $args->getClassName());
                Assert::assertSame($this->em, $args->getObjectManager());

                $args->setFoundMetadata($this->metadata);
            }
        });

        self::assertSame($metadata, $cmf->getMetadataFor('Foo'));
    }

    /** @group DDC-3427 */
    public function testAcceptsEntityManagerInterfaceInstances(): void
    {
        $classMetadataFactory = new ClassMetadataFactory();

        $entityManager = $this->createMock(EntityManagerInterface::class);
        $classMetadataFactory->setEntityManager($entityManager);

        // not really the cleanest way to check it, but we won't add a getter to the CMF just for the sake of testing.
        $class    = new ReflectionClass(ClassMetadataFactory::class);
        $property = $class->getProperty('em');
        $property->setAccessible(true);
        self::assertSame($entityManager, $property->getValue($classMetadataFactory));
    }

    /** @group DDC-3305 */
    public function testRejectsEmbeddableWithoutValidClassName(): void
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

    /** @group DDC-4006 */
    public function testInheritsIdGeneratorMappingFromEmbeddable(): void
    {
        $cmf    = new ClassMetadataFactory();
        $driver = $this->createAnnotationDriver([__DIR__ . '/../../Models/DDC4006/']);
        $em     = $this->createEntityManager($driver);
        $cmf->setEntityManager($em);

        $userMetadata = $cmf->getMetadataFor(DDC4006User::class);

        self::assertTrue($userMetadata->isIdGeneratorIdentity());
    }

    public function testInvalidSubClassCase(): void
    {
        $this->expectException(MappingException::class);
        $this->expectExceptionMessage('Entity class \'Doctrine\Tests\ORM\Mapping\cube\' used in the discriminator map of class \'Doctrine\Tests\ORM\Mapping\Shape\' does not exist.');

        $cmf    = new ClassMetadataFactory();
        $driver = $this->createAnnotationDriver([__DIR__]);
        $em     = $this->createEntityManager($driver);
        $cmf->setEntityManager($em);

        $userMetadata = $cmf->getMetadataFor(Shape::class);
    }
}

/**
 * @Entity
 * @InheritanceType("SINGLE_TABLE")
 * @DiscriminatorMap({"cube" = cube::class})
 * @DiscriminatorColumn(name="discr", length=32, type="string")
 */
abstract class Shape
{
    /**
     * @var string
     * @Id
     * @Column(type="string", length=255)
     * @GeneratedValue(strategy="AUTO")
     */
    public $id;
}

/** @Entity */
final class Cube extends Shape
{
}

/* Test subject class with overridden factory method for mocking purposes */
class ClassMetadataFactoryTestSubject extends ClassMetadataFactory
{
    /** @psalm-var array<class-string<object>, ClassMetadata> */
    private $mockMetadata = [];

    /** @psalm-var list<class-string<object>> */
    private $requestedClasses = [];

    /** @psalm-param class-string<object> $className */
    protected function newClassMetadataInstance($className): ClassMetadata
    {
        $this->requestedClasses[] = $className;
        if (! isset($this->mockMetadata[$className])) {
            throw new InvalidArgumentException(sprintf(
                'No mock metadata found for class %s.',
                $className
            ));
        }

        return $this->mockMetadata[$className];
    }

    /** @psalm-param class-string<object> $className */
    public function setMetadataForClass(string $className, ClassMetadata $metadata): void
    {
        $this->mockMetadata[$className] = $metadata;
    }

    /** @return list<class-string<object>> */
    public function getRequestedClasses(): array
    {
        return $this->requestedClasses;
    }
}

class TestEntity1
{
    /** @var int */
    private $id;

    /** @var string */
    private $name;

    /** @var mixed */
    private $other;

    /** @var mixed */
    private $association;

    /** @var mixed */
    private $embedded;
}

class CustomIdGenerator extends AbstractIdGenerator
{
    /**
     * {@inheritdoc}
     */
    public function generateId(EntityManagerInterface $em, $entity): string
    {
        return 'foo';
    }
}
