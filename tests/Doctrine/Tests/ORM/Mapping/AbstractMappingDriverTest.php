<?php

declare(strict_types=1);

namespace Doctrine\Tests\ORM\Mapping;

use Doctrine\DBAL\Types\Type;
use Doctrine\ORM\Annotation as ORM;
use Doctrine\ORM\EntityManagerInterface;
use Doctrine\ORM\Events;
use Doctrine\ORM\Mapping;
use Doctrine\ORM\Mapping\ClassMetadata;
use Doctrine\ORM\Mapping\ClassMetadataFactory;
use Doctrine\ORM\Mapping\Factory\DefaultNamingStrategy;
use Doctrine\ORM\Mapping\Factory\UnderscoreNamingStrategy;
use Doctrine\ORM\Mapping\MappingException;
use Doctrine\ORM\Reflection\RuntimeReflectionService;
use Doctrine\Tests\Models\Cache\City;
use Doctrine\Tests\Models\CMS\CmsAddress;
use Doctrine\Tests\Models\CMS\CmsAddressListener;
use Doctrine\Tests\Models\Company\CompanyContract;
use Doctrine\Tests\Models\Company\CompanyContractListener;
use Doctrine\Tests\Models\Company\CompanyFixContract;
use Doctrine\Tests\Models\Company\CompanyFlexContract;
use Doctrine\Tests\Models\Company\CompanyFlexUltraContract;
use Doctrine\Tests\Models\Company\CompanyFlexUltraContractListener;
use Doctrine\Tests\Models\DDC1476\DDC1476EntityWithDefaultFieldType;
use Doctrine\Tests\Models\DDC2825\ExplicitSchemaAndTable;
use Doctrine\Tests\Models\DDC2825\SchemaAndTableInTableName;
use Doctrine\Tests\Models\DDC3579\DDC3579Admin;
use Doctrine\Tests\Models\DDC5934\DDC5934Contract;
use Doctrine\Tests\Models\DDC869\DDC869ChequePayment;
use Doctrine\Tests\Models\DDC869\DDC869CreditCardPayment;
use Doctrine\Tests\Models\DDC869\DDC869PaymentRepository;
use Doctrine\Tests\Models\DDC889\DDC889Class;
use Doctrine\Tests\Models\DDC889\DDC889Entity;
use Doctrine\Tests\Models\DDC964\DDC964Admin;
use Doctrine\Tests\Models\DDC964\DDC964Guest;
use Doctrine\Tests\Models\Quote;
use Doctrine\Tests\OrmTestCase;
use const CASE_UPPER;
use function get_class;
use function iterator_to_array;
use function reset;
use function strpos;

abstract class AbstractMappingDriverTest extends OrmTestCase
{
    /** @var Mapping\ClassMetadataBuildingContext */
    protected $metadataBuildingContext;

    protected function setUp() : void
    {
        parent::setUp();

        $this->metadataBuildingContext = new Mapping\ClassMetadataBuildingContext(
            $this->createMock(ClassMetadataFactory::class),
            new RuntimeReflectionService()
        );
    }

    abstract protected function loadDriver();

    public function createClassMetadata($entityClassName)
    {
        $mappingDriver = $this->loadDriver();

        $class = new ClassMetadata($entityClassName, $this->metadataBuildingContext);

        $mappingDriver->loadMetadataForClass($entityClassName, $class, $this->metadataBuildingContext);

        return $class;
    }

    protected function createClassMetadataFactory(?EntityManagerInterface $em = null) : ClassMetadataFactory
    {
        $driver  = $this->loadDriver();
        $em      = $em ?: $this->getTestEntityManager();
        $factory = new ClassMetadataFactory();

        $em->getConfiguration()->setMetadataDriverImpl($driver);
        $factory->setEntityManager($em);

        return $factory;
    }

    public function testEntityTableNameAndInheritance() : ClassMetadata
    {
        $class = $this->createClassMetadata(User::class);

        self::assertEquals('cms_users', $class->getTableName());
        self::assertEquals(Mapping\InheritanceType::NONE, $class->inheritanceType);

        return $class;
    }

    public function testEntityIndexes() : ClassMetadata
    {
        $class = $this->createClassMetadata('Doctrine\Tests\ORM\Mapping\User');

        self::assertCount(2, $class->table->getIndexes());
        self::assertEquals(
            [
                'name_idx' => [
                    'name'    => 'name_idx',
                    'columns' => ['name'],
                    'unique'  => false,
                    'options' => [],
                    'flags'   => [],
                ],
                0 => [
                    'name'    => null,
                    'columns' => ['user_email'],
                    'unique'  => false,
                    'options' => [],
                    'flags'   => [],
                ],
            ],
            $class->table->getIndexes()
        );

        return $class;
    }

    public function testEntityIndexFlagsAndPartialIndexes() : void
    {
        $class = $this->createClassMetadata(Comment::class);

        self::assertEquals(
            [
                0 => [
                    'name'    => null,
                    'columns' => ['content'],
                    'unique'  => false,
                    'flags'   => ['fulltext'],
                    'options' => ['where' => 'content IS NOT NULL'],
                ],
            ],
            $class->table->getIndexes()
        );
    }

    /**
     * @param ClassMetadata $class
     *
     * @depends testEntityTableNameAndInheritance
     */
    public function testEntityUniqueConstraints($class) : ClassMetadata
    {
        self::assertCount(1, $class->table->getUniqueConstraints());
        self::assertEquals(
            [
                'search_idx' => [
                    'name'    => 'search_idx',
                    'columns' => ['name', 'user_email'],
                    'options' => [],
                    'flags'   => [],
                ],
            ],
            $class->table->getUniqueConstraints()
        );

        return $class;
    }

    /**
     * @param ClassMetadata $class
     *
     * @depends testEntityTableNameAndInheritance
     */
    public function testEntityOptions($class) : ClassMetadata
    {
        self::assertCount(2, $class->table->getOptions());
        self::assertEquals(
            [
                'foo' => 'bar',
                'baz' => ['key' => 'val'],
            ],
            $class->table->getOptions()
        );

        return $class;
    }

    /**
     * @param ClassMetadata $class
     *
     * @depends testEntityOptions
     */
    public function testEntitySequence($class) : void
    {
        self::assertInternalType(
            'array',
            $class->getProperty('id')->getValueGenerator()->getDefinition(),
            'No Sequence Definition set on this driver.'
        );
        self::assertEquals(
            [
                'sequenceName'   => 'tablename_seq',
                'allocationSize' => 100,
            ],
            $class->getProperty('id')->getValueGenerator()->getDefinition()
        );
    }

    public function testEntityCustomGenerator() : void
    {
        $class = $this->createClassMetadata(Animal::class);

        self::assertEquals(Mapping\GeneratorType::CUSTOM, $class->getProperty('id')->getValueGenerator()->getType(), 'Generator Type');
        self::assertEquals(
            [
                'class'     => 'stdClass',
                'arguments' => [],
            ],
            $class->getProperty('id')->getValueGenerator()->getDefinition(),
            'Generator Definition'
        );
    }


    /**
     * @param ClassMetadata $class
     *
     * @depends testEntityTableNameAndInheritance
     */
    public function testProperties($class) : ClassMetadata
    {
        self::assertCount(7, $class->getDeclaredPropertiesIterator());

        self::assertNotNull($class->getProperty('id'));
        self::assertNotNull($class->getProperty('name'));
        self::assertNotNull($class->getProperty('email'));
        self::assertNotNull($class->getProperty('version'));
        self::assertNotNull($class->getProperty('version'));

        return $class;
    }

    /**
     * @param ClassMetadata $class
     *
     * @depends testProperties
     */
    public function testVersionProperty($class) : void
    {
        self::assertTrue($class->isVersioned());
        self::assertNotNull($class->versionProperty);

        $versionPropertyName = $class->versionProperty->getName();

        self::assertEquals('version', $versionPropertyName);
        self::assertNotNull($class->getProperty($versionPropertyName));
    }

    /**
     * @param ClassMetadata $class
     *
     * @depends testEntityTableNameAndInheritance
     */
    public function testFieldMappingsColumnNames($class) : ClassMetadata
    {
        self::assertNotNull($class->getProperty('id'));
        self::assertNotNull($class->getProperty('name'));
        self::assertNotNull($class->getProperty('email'));

        self::assertEquals('id', $class->getProperty('id')->getColumnName());
        self::assertEquals('name', $class->getProperty('name')->getColumnName());
        self::assertEquals('user_email', $class->getProperty('email')->getColumnName());

        return $class;
    }

    /**
     * @param ClassMetadata $class
     *
     * @depends testEntityTableNameAndInheritance
     */
    public function testStringFieldMappings($class) : ClassMetadata
    {
        self::assertNotNull($class->getProperty('name'));

        $property = $class->getProperty('name');

        self::assertEquals('string', $property->getTypeName());
        self::assertEquals(50, $property->getLength());
        self::assertTrue($property->isNullable());
        self::assertTrue($property->isUnique());

        return $class;
    }

    /**
     * @depends testEntityTableNameAndInheritance
     */
    public function testFieldOptions(ClassMetadata $class) : ClassMetadata
    {
        self::assertNotNull($class->getProperty('name'));

        $property = $class->getProperty('name');
        $expected = ['foo' => 'bar', 'baz' => ['key' => 'val'], 'fixed' => false];

        self::assertEquals($expected, $property->getOptions());

        return $class;
    }

    /**
     * @param ClassMetadata $class
     *
     * @depends testEntityTableNameAndInheritance
     */
    public function testIdFieldOptions($class) : ClassMetadata
    {
        self::assertNotNull($class->getProperty('id'));

        $property = $class->getProperty('id');
        $expected = ['foo' => 'bar', 'unsigned' => false];

        self::assertEquals($expected, $property->getOptions());

        return $class;
    }

    /**
     * @param ClassMetadata $class
     *
     * @depends testProperties
     */
    public function testIdentifier($class) : ClassMetadata
    {
        self::assertNotNull($class->getProperty('id'));

        $property = $class->getProperty('id');

        self::assertEquals('integer', $property->getTypeName());
        self::assertEquals(['id'], $class->identifier);
        self::assertEquals(Mapping\GeneratorType::AUTO, $property->getValueGenerator()->getType(), 'ID-Generator is not GeneratorType::AUTO');

        return $class;
    }

    /**
     * @group #6129
     */
    public function testBooleanValuesForOptionIsSetCorrectly() : ClassMetadata
    {
        $class = $this->createClassMetadata(User::class);

        $idOptions   = $class->getProperty('id')->getOptions();
        $nameOptions = $class->getProperty('name')->getOptions();

        self::assertInternalType('bool', $idOptions['unsigned']);
        self::assertFalse($idOptions['unsigned']);
        self::assertInternalType('bool', $nameOptions['fixed']);
        self::assertFalse($nameOptions['fixed']);

        return $class;
    }

    public function testOneToOneUnidirectional() : void
    {
        // One to One owning
        $fullAddressClass = $this->createClassMetadata(Quote\FullAddress::class);
        $cityAssociation  = $fullAddressClass->getProperty('city');

        self::assertInstanceOf(Mapping\OneToOneAssociationMetadata::class, $cityAssociation);
        self::assertTrue($cityAssociation->isOwningSide());
    }

    public function testOneToOneBidirectional() : void
    {
        // One to One owning / One To One inverse
        $addressClass    = $this->createClassMetadata(Quote\Address::class);
        $userAssociation = $addressClass->getProperty('user');

        self::assertInstanceOf(Mapping\OneToOneAssociationMetadata::class, $userAssociation);
        self::assertTrue($userAssociation->isOwningSide());

        $userClass          = $this->createClassMetadata(Quote\User::class);
        $addressAssociation = $userClass->getProperty('address');

        self::assertInstanceOf(Mapping\OneToOneAssociationMetadata::class, $addressAssociation);
        self::assertFalse($addressAssociation->isOwningSide());
    }

    public function testManyToOneUnidirectional() : void
    {
        // Many to One owning
        $groupClass       = $this->createClassMetadata(Quote\Group::class);
        $groupAssociation = $groupClass->getProperty('parent');

        self::assertInstanceOf(Mapping\ManyToOneAssociationMetadata::class, $groupAssociation);
        self::assertTrue($groupAssociation->isOwningSide());
    }

    public function testManyToOneBidirectional() : void
    {
        // Many To One owning / One To Many inverse
        $phoneClass      = $this->createClassMetadata(Quote\Phone::class);
        $userAssociation = $phoneClass->getProperty('user');

        self::assertInstanceOf(Mapping\ManyToOneAssociationMetadata::class, $userAssociation);
        self::assertTrue($userAssociation->isOwningSide());

        $userClass        = $this->createClassMetadata(Quote\User::class);
        $phoneAssociation = $userClass->getProperty('phones');

        self::assertInstanceOf(Mapping\OneToManyAssociationMetadata::class, $phoneAssociation);
        self::assertFalse($phoneAssociation->isOwningSide());
    }

    public function testManyToManyBidirectional() : void
    {
        // Many to Many owning / Many to Many inverse
        $userClass        = $this->createClassMetadata(Quote\User::class);
        $groupAssociation = $userClass->getProperty('groups');

        self::assertInstanceOf(Mapping\ManyToManyAssociationMetadata::class, $groupAssociation);
        self::assertTrue($groupAssociation->isOwningSide());

        $groupClass      = $this->createClassMetadata(Quote\Group::class);
        $userAssociation = $groupClass->getProperty('users');

        self::assertInstanceOf(Mapping\ManyToManyAssociationMetadata::class, $userAssociation);
        self::assertFalse($userAssociation->isOwningSide());
    }

    /**
     * @param ClassMetadata $class
     *
     * @depends testProperties
     */
    public function testOwningOneToOneAssociation($class) : ClassMetadata
    {
        self::assertArrayHasKey('address', iterator_to_array($class->getDeclaredPropertiesIterator()));

        $association = $class->getProperty('address');

        self::assertTrue($association->isOwningSide());
        self::assertEquals('user', $association->getInversedBy());
        // Check cascading
        self::assertEquals(['remove'], $association->getCascade());

        return $class;
    }

    /**
     * @param ClassMetadata $class
     *
     * @depends testOwningOneToOneAssociation
     */
    public function testInverseOneToManyAssociation($class) : ClassMetadata
    {
        self::assertArrayHasKey('phonenumbers', iterator_to_array($class->getDeclaredPropertiesIterator()));

        $association = $class->getProperty('phonenumbers');

        self::assertFalse($association->isOwningSide());
        self::assertTrue($association->isOrphanRemoval());

        // Check cascading
        self::assertEquals(['persist', 'remove'], $association->getCascade());

        // Test Order By
        self::assertEquals(['number' => 'ASC'], $association->getOrderBy());

        return $class;
    }

    /**
     * @param ClassMetadata $class
     *
     * @depends testInverseOneToManyAssociation
     */
    public function testManyToManyAssociationWithCascadeAll($class) : ClassMetadata
    {
        self::assertArrayHasKey('groups', iterator_to_array($class->getDeclaredPropertiesIterator()));

        $association = $class->getProperty('groups');

        self::assertTrue($association->isOwningSide());

        // Make sure that cascade-all works as expected
        self::assertEquals(['remove', 'persist', 'refresh'], $association->getCascade());

        // Test Order By
        self::assertEquals([], $association->getOrderBy());

        return $class;
    }

    /**
     * @param ClassMetadata $class
     *
     * @depends testManyToManyAssociationWithCascadeAll
     */
    public function testLifecycleCallbacks($class) : ClassMetadata
    {
        self::assertCount(2, $class->lifecycleCallbacks);
        self::assertEquals($class->lifecycleCallbacks['prePersist'][0], 'doStuffOnPrePersist');
        self::assertEquals($class->lifecycleCallbacks['postPersist'][0], 'doStuffOnPostPersist');

        return $class;
    }

    /**
     * @param ClassMetadata $class
     *
     * @depends testManyToManyAssociationWithCascadeAll
     */
    public function testLifecycleCallbacksSupportMultipleMethodNames($class) : ClassMetadata
    {
        self::assertCount(2, $class->lifecycleCallbacks['prePersist']);
        self::assertEquals($class->lifecycleCallbacks['prePersist'][1], 'doOtherStuffOnPrePersistToo');

        return $class;
    }

    /**
     * @param ClassMetadata $class
     *
     * @depends testLifecycleCallbacksSupportMultipleMethodNames
     */
    public function testJoinColumnUniqueAndNullable($class) : ClassMetadata
    {
        // Non-Nullability of Join Column
        $association = $class->getProperty('groups');
        $joinTable   = $association->getJoinTable();
        $joinColumns = $joinTable->getJoinColumns();
        $joinColumn  = reset($joinColumns);

        self::assertFalse($joinColumn->isNullable());
        self::assertFalse($joinColumn->isUnique());

        return $class;
    }

    /**
     * @param ClassMetadata $class
     *
     * @depends testJoinColumnUniqueAndNullable
     */
    public function testColumnDefinition($class) : ClassMetadata
    {
        self::assertNotNull($class->getProperty('email'));

        $property           = $class->getProperty('email');
        $association        = $class->getProperty('groups');
        $joinTable          = $association->getJoinTable();
        $inverseJoinColumns = $joinTable->getInverseJoinColumns();
        $inverseJoinColumn  = reset($inverseJoinColumns);

        self::assertEquals('CHAR(32) NOT NULL', $property->getColumnDefinition());
        self::assertEquals('INT NULL', $inverseJoinColumn->getColumnDefinition());

        return $class;
    }

    /**
     * @param ClassMetadata $class
     *
     * @depends testColumnDefinition
     */
    public function testJoinColumnOnDelete($class) : ClassMetadata
    {
        $association = $class->getProperty('address');
        $joinColumns = $association->getJoinColumns();
        $joinColumn  = reset($joinColumns);

        self::assertEquals('CASCADE', $joinColumn->getOnDelete());

        return $class;
    }

    /**
     * @group DDC-514
     */
    public function testDiscriminatorColumnDefaults() : void
    {
        if (strpos(static::class, 'PHPMappingDriver') !== false) {
            $this->markTestSkipped('PHP Mapping Drivers have no defaults.');
        }

        $class = $this->createClassMetadata(Animal::class);

        self::assertNotNull($class->discriminatorColumn);

        $discrColumn = $class->discriminatorColumn;

        self::assertEquals('Animal', $discrColumn->getTableName());
        self::assertEquals('discr', $discrColumn->getColumnName());
        self::assertEquals('string', $discrColumn->getTypeName());
        self::assertEquals(32, $discrColumn->getLength());
        self::assertNull($discrColumn->getColumnDefinition());
    }

    /**
     * @group DDC-869
     */
    public function testMappedSuperclassWithRepository() : void
    {
        $em      = $this->getTestEntityManager();
        $factory = $this->createClassMetadataFactory($em);
        $class   = $factory->getMetadataFor(DDC869CreditCardPayment::class);

        self::assertNotNull($class->getProperty('id'));
        self::assertNotNull($class->getProperty('value'));
        self::assertNotNull($class->getProperty('creditCardNumber'));
        self::assertEquals($class->getCustomRepositoryClassName(), DDC869PaymentRepository::class);
        self::assertInstanceOf(DDC869PaymentRepository::class, $em->getRepository(DDC869CreditCardPayment::class));
        self::assertTrue($em->getRepository(DDC869ChequePayment::class)->isTrue());

        $class = $factory->getMetadataFor(DDC869ChequePayment::class);

        self::assertNotNull($class->getProperty('id'));
        self::assertNotNull($class->getProperty('value'));
        self::assertNotNull($class->getProperty('serialNumber'));
        self::assertEquals($class->getCustomRepositoryClassName(), DDC869PaymentRepository::class);
        self::assertInstanceOf(DDC869PaymentRepository::class, $em->getRepository(DDC869ChequePayment::class));
        self::assertTrue($em->getRepository(DDC869ChequePayment::class)->isTrue());
    }

    /**
     * @group DDC-1476
     */
    public function testDefaultFieldType() : void
    {
        $factory = $this->createClassMetadataFactory();
        $class   = $factory->getMetadataFor(DDC1476EntityWithDefaultFieldType::class);

        self::assertNotNull($class->getProperty('id'));
        self::assertNotNull($class->getProperty('name'));

        $idProperty   = $class->getProperty('id');
        $nameProperty = $class->getProperty('name');

        self::assertInstanceOf(Mapping\FieldMetadata::class, $idProperty);
        self::assertInstanceOf(Mapping\FieldMetadata::class, $nameProperty);

        self::assertEquals('string', $idProperty->getTypeName());
        self::assertEquals('string', $nameProperty->getTypeName());

        self::assertEquals('id', $idProperty->getName());
        self::assertEquals('name', $nameProperty->getName());

        self::assertEquals('id', $idProperty->getColumnName());
        self::assertEquals('name', $nameProperty->getColumnName());

        self::assertFalse($idProperty->hasValueGenerator());
    }

    /**
     * @group DDC-1170
     */
    public function testIdentifierColumnDefinition() : void
    {
        $class = $this->createClassMetadata(DDC1170Entity::class);

        self::assertNotNull($class->getProperty('id'));
        self::assertNotNull($class->getProperty('value'));

        self::assertEquals('INT unsigned NOT NULL', $class->getProperty('id')->getColumnDefinition());
        self::assertEquals('VARCHAR(255) NOT NULL', $class->getProperty('value')->getColumnDefinition());
    }

    /**
     * @group DDC-559
     */
    public function testNamingStrategy() : void
    {
        $em      = $this->getTestEntityManager();
        $factory = $this->createClassMetadataFactory($em);

        self::assertInstanceOf(DefaultNamingStrategy::class, $em->getConfiguration()->getNamingStrategy());
        $em->getConfiguration()->setNamingStrategy(new UnderscoreNamingStrategy(CASE_UPPER));
        self::assertInstanceOf(UnderscoreNamingStrategy::class, $em->getConfiguration()->getNamingStrategy());

        $class        = $factory->getMetadataFor(DDC1476EntityWithDefaultFieldType::class);
        $idProperty   = $class->getProperty('id');
        $nameProperty = $class->getProperty('name');

        self::assertEquals('ID', $idProperty->getColumnName());
        self::assertEquals('NAME', $nameProperty->getColumnName());
        self::assertEquals('DDC1476ENTITY_WITH_DEFAULT_FIELD_TYPE', $class->table->getName());
    }

    /**
     * @group DDC-807
     * @group DDC-553
     */
    public function testDiscriminatorColumnDefinition() : void
    {
        $class = $this->createClassMetadata(DDC807Entity::class);

        self::assertNotNull($class->discriminatorColumn);

        $discrColumn = $class->discriminatorColumn;

        self::assertEquals('dtype', $discrColumn->getColumnName());
        self::assertEquals("ENUM('ONE','TWO')", $discrColumn->getColumnDefinition());
    }

    /**
     * @group DDC-889
     */
    public function testInvalidEntityOrMappedSuperClassShouldMentionParentClasses() : void
    {
        $this->expectException(MappingException::class);
        $this->expectExceptionMessage('Class "Doctrine\Tests\Models\DDC889\DDC889Class" sub class of "Doctrine\Tests\Models\DDC889\DDC889SuperClass" is not a valid entity or mapped super class.');

        $this->createClassMetadata(DDC889Class::class);
    }

    /**
     * @group DDC-889
     */
    public function testIdentifierRequiredShouldMentionParentClasses() : void
    {
        $factory = $this->createClassMetadataFactory();

        $this->expectException(MappingException::class);
        $this->expectExceptionMessage('No identifier/primary key specified for Entity "Doctrine\Tests\Models\DDC889\DDC889Entity" sub class of "Doctrine\Tests\Models\DDC889\DDC889SuperClass". Every Entity must have an identifier/primary key.');

        $factory->getMetadataFor(DDC889Entity::class);
    }

    /**
     * @group DDC-964
     */
    public function testAssociationOverridesMapping() : void
    {
        $factory       = $this->createClassMetadataFactory();
        $adminMetadata = $factory->getMetadataFor(DDC964Admin::class);
        $guestMetadata = $factory->getMetadataFor(DDC964Guest::class);

        // assert groups association mappings
        self::assertArrayHasKey('groups', iterator_to_array($guestMetadata->getDeclaredPropertiesIterator()));
        self::assertArrayHasKey('groups', iterator_to_array($adminMetadata->getDeclaredPropertiesIterator()));

        $guestGroups = $guestMetadata->getProperty('groups');
        $adminGroups = $adminMetadata->getProperty('groups');

        // assert not override attributes
        self::assertEquals($guestGroups->getName(), $adminGroups->getName());
        self::assertEquals(get_class($guestGroups), get_class($adminGroups));
        self::assertEquals($guestGroups->getMappedBy(), $adminGroups->getMappedBy());
        self::assertEquals($guestGroups->getInversedBy(), $adminGroups->getInversedBy());
        self::assertEquals($guestGroups->isOwningSide(), $adminGroups->isOwningSide());
        self::assertEquals($guestGroups->getFetchMode(), $adminGroups->getFetchMode());
        self::assertEquals($guestGroups->getCascade(), $adminGroups->getCascade());

        // assert not override attributes
        $guestGroupsJoinTable          = $guestGroups->getJoinTable();
        $guestGroupsJoinColumns        = $guestGroupsJoinTable->getJoinColumns();
        $guestGroupsJoinColumn         = reset($guestGroupsJoinColumns);
        $guestGroupsInverseJoinColumns = $guestGroupsJoinTable->getInverseJoinColumns();
        $guestGroupsInverseJoinColumn  = reset($guestGroupsInverseJoinColumns);

        self::assertEquals('ddc964_users_groups', $guestGroupsJoinTable->getName());
        self::assertEquals('user_id', $guestGroupsJoinColumn->getColumnName());
        self::assertEquals('group_id', $guestGroupsInverseJoinColumn->getColumnName());

        $adminGroupsJoinTable          = $adminGroups->getJoinTable();
        $adminGroupsJoinColumns        = $adminGroupsJoinTable->getJoinColumns();
        $adminGroupsJoinColumn         = reset($adminGroupsJoinColumns);
        $adminGroupsInverseJoinColumns = $adminGroupsJoinTable->getInverseJoinColumns();
        $adminGroupsInverseJoinColumn  = reset($adminGroupsInverseJoinColumns);

        self::assertEquals('ddc964_users_admingroups', $adminGroupsJoinTable->getName());
        self::assertEquals('adminuser_id', $adminGroupsJoinColumn->getColumnName());
        self::assertEquals('admingroup_id', $adminGroupsInverseJoinColumn->getColumnName());

        // assert address association mappings
        self::assertArrayHasKey('address', iterator_to_array($guestMetadata->getDeclaredPropertiesIterator()));
        self::assertArrayHasKey('address', iterator_to_array($adminMetadata->getDeclaredPropertiesIterator()));

        $guestAddress = $guestMetadata->getProperty('address');
        $adminAddress = $adminMetadata->getProperty('address');

        // assert not override attributes
        self::assertEquals($guestAddress->getName(), $adminAddress->getName());
        self::assertEquals(get_class($guestAddress), get_class($adminAddress));
        self::assertEquals($guestAddress->getMappedBy(), $adminAddress->getMappedBy());
        self::assertEquals($guestAddress->getInversedBy(), $adminAddress->getInversedBy());
        self::assertEquals($guestAddress->isOwningSide(), $adminAddress->isOwningSide());
        self::assertEquals($guestAddress->getFetchMode(), $adminAddress->getFetchMode());
        self::assertEquals($guestAddress->getCascade(), $adminAddress->getCascade());

        // assert override
        $guestAddressJoinColumns = $guestAddress->getJoinColumns();
        $guestAddressJoinColumn  = reset($guestAddressJoinColumns);

        self::assertEquals('address_id', $guestAddressJoinColumn->getColumnName());

        $adminAddressJoinColumns = $adminAddress->getJoinColumns();
        $adminAddressJoinColumn  = reset($adminAddressJoinColumns);

        self::assertEquals('adminaddress_id', $adminAddressJoinColumn->getColumnName());
    }

    /**
     * @group DDC-3579
     */
    public function testInversedByOverrideMapping() : void
    {
        $factory       = $this->createClassMetadataFactory();
        $adminMetadata = $factory->getMetadataFor(DDC3579Admin::class);

        // assert groups association mappings
        self::assertArrayHasKey('groups', iterator_to_array($adminMetadata->getDeclaredPropertiesIterator()));

        $adminGroups = $adminMetadata->getProperty('groups');

        // assert override
        self::assertEquals('admins', $adminGroups->getInversedBy());
    }

    /**
     * @group DDC-5934
     */
    public function testFetchOverrideMapping() : void
    {
        // check override metadata
        $contractMetadata = $this->createClassMetadataFactory()->getMetadataFor(DDC5934Contract::class);

        self::assertArrayHasKey('members', iterator_to_array($contractMetadata->getDeclaredPropertiesIterator()));

        $contractMembers = $contractMetadata->getProperty('members');

        self::assertSame(Mapping\FetchMode::EXTRA_LAZY, $contractMembers->getFetchMode());
    }

    /**
     * @group DDC-964
     */
    public function testAttributeOverridesMapping() : void
    {
        $factory       = $this->createClassMetadataFactory();
        $adminMetadata = $factory->getMetadataFor(DDC964Admin::class);

        self::assertEquals(
            [
                'user_id' => 'id',
                'user_name' => 'name',
                'adminaddress_id' => 'address',
            ],
            $adminMetadata->fieldNames
        );

        self::assertNotNull($adminMetadata->getProperty('id'));

        $idProperty = $adminMetadata->getProperty('id');

        self::assertTrue($idProperty->isPrimaryKey());
        self::assertEquals('id', $idProperty->getName());
        self::assertEquals('user_id', $idProperty->getColumnName());

        self::assertNotNull($adminMetadata->getProperty('name'));

        $nameProperty = $adminMetadata->getProperty('name');

        self::assertEquals('name', $nameProperty->getName());
        self::assertEquals('user_name', $nameProperty->getColumnName());
        self::assertEquals(250, $nameProperty->getLength());
        self::assertTrue($nameProperty->isNullable());
        self::assertFalse($nameProperty->isUnique());

        $guestMetadata = $factory->getMetadataFor(DDC964Guest::class);

        self::assertEquals(
            [
                'guest_id' => 'id',
                'guest_name' => 'name',
                'address_id' => 'address',
            ],
            $guestMetadata->fieldNames
        );

        self::assertNotNull($guestMetadata->getProperty('id'));

        $idProperty = $guestMetadata->getProperty('id');

        self::assertTrue($idProperty->isPrimaryKey());
        self::assertEquals('id', $idProperty->getName());
        self::assertEquals('guest_id', $idProperty->getColumnName());

        self::assertNotNull($guestMetadata->getProperty('name'));

        $nameProperty = $guestMetadata->getProperty('name');

        self::assertEquals('name', $nameProperty->getName());
        self::assertEquals('guest_name', $nameProperty->getColumnName());
        self::assertEquals(240, $nameProperty->getLength());
        self::assertFalse($nameProperty->isNullable());
        self::assertTrue($nameProperty->isUnique());
    }

    /**
     * @group DDC-1955
     */
    public function testEntityListeners() : void
    {
        $factory    = $this->createClassMetadataFactory();
        $superClass = $factory->getMetadataFor(CompanyContract::class);
        $flexClass  = $factory->getMetadataFor(CompanyFixContract::class);
        $fixClass   = $factory->getMetadataFor(CompanyFlexContract::class);

        self::assertArrayHasKey(Events::prePersist, $superClass->entityListeners);
        self::assertArrayHasKey(Events::postPersist, $superClass->entityListeners);

        self::assertCount(1, $superClass->entityListeners[Events::prePersist]);
        self::assertCount(1, $superClass->entityListeners[Events::postPersist]);

        $postPersist = $superClass->entityListeners[Events::postPersist][0];
        $prePersist  = $superClass->entityListeners[Events::prePersist][0];

        self::assertEquals(CompanyContractListener::class, $postPersist['class']);
        self::assertEquals(CompanyContractListener::class, $prePersist['class']);
        self::assertEquals('postPersistHandler', $postPersist['method']);
        self::assertEquals('prePersistHandler', $prePersist['method']);

        //Inherited listeners
        self::assertEquals($fixClass->entityListeners, $superClass->entityListeners);
        self::assertEquals($flexClass->entityListeners, $superClass->entityListeners);
    }

    /**
     * @group DDC-1955
     */
    public function testEntityListenersOverride() : void
    {
        $factory    = $this->createClassMetadataFactory();
        $ultraClass = $factory->getMetadataFor(CompanyFlexUltraContract::class);

        //overridden listeners
        self::assertArrayHasKey(Events::postPersist, $ultraClass->entityListeners);
        self::assertArrayHasKey(Events::prePersist, $ultraClass->entityListeners);

        self::assertCount(1, $ultraClass->entityListeners[Events::postPersist]);
        self::assertCount(3, $ultraClass->entityListeners[Events::prePersist]);

        $postPersist = $ultraClass->entityListeners[Events::postPersist][0];
        $prePersist  = $ultraClass->entityListeners[Events::prePersist][0];

        self::assertEquals(CompanyContractListener::class, $postPersist['class']);
        self::assertEquals(CompanyContractListener::class, $prePersist['class']);
        self::assertEquals('postPersistHandler', $postPersist['method']);
        self::assertEquals('prePersistHandler', $prePersist['method']);

        $prePersist = $ultraClass->entityListeners[Events::prePersist][1];
        self::assertEquals(CompanyFlexUltraContractListener::class, $prePersist['class']);
        self::assertEquals('prePersistHandler1', $prePersist['method']);

        $prePersist = $ultraClass->entityListeners[Events::prePersist][2];
        self::assertEquals(CompanyFlexUltraContractListener::class, $prePersist['class']);
        self::assertEquals('prePersistHandler2', $prePersist['method']);
    }


    /**
     * @group DDC-1955
     */
    public function testEntityListenersNamingConvention() : void
    {
        $factory  = $this->createClassMetadataFactory();
        $metadata = $factory->getMetadataFor(CmsAddress::class);

        self::assertArrayHasKey(Events::postPersist, $metadata->entityListeners);
        self::assertArrayHasKey(Events::prePersist, $metadata->entityListeners);
        self::assertArrayHasKey(Events::postUpdate, $metadata->entityListeners);
        self::assertArrayHasKey(Events::preUpdate, $metadata->entityListeners);
        self::assertArrayHasKey(Events::postRemove, $metadata->entityListeners);
        self::assertArrayHasKey(Events::preRemove, $metadata->entityListeners);
        self::assertArrayHasKey(Events::postLoad, $metadata->entityListeners);
        self::assertArrayHasKey(Events::preFlush, $metadata->entityListeners);

        self::assertCount(1, $metadata->entityListeners[Events::postPersist]);
        self::assertCount(1, $metadata->entityListeners[Events::prePersist]);
        self::assertCount(1, $metadata->entityListeners[Events::postUpdate]);
        self::assertCount(1, $metadata->entityListeners[Events::preUpdate]);
        self::assertCount(1, $metadata->entityListeners[Events::postRemove]);
        self::assertCount(1, $metadata->entityListeners[Events::preRemove]);
        self::assertCount(1, $metadata->entityListeners[Events::postLoad]);
        self::assertCount(1, $metadata->entityListeners[Events::preFlush]);

        $postPersist = $metadata->entityListeners[Events::postPersist][0];
        $prePersist  = $metadata->entityListeners[Events::prePersist][0];
        $postUpdate  = $metadata->entityListeners[Events::postUpdate][0];
        $preUpdate   = $metadata->entityListeners[Events::preUpdate][0];
        $postRemove  = $metadata->entityListeners[Events::postRemove][0];
        $preRemove   = $metadata->entityListeners[Events::preRemove][0];
        $postLoad    = $metadata->entityListeners[Events::postLoad][0];
        $preFlush    = $metadata->entityListeners[Events::preFlush][0];

        self::assertEquals(CmsAddressListener::class, $postPersist['class']);
        self::assertEquals(CmsAddressListener::class, $prePersist['class']);
        self::assertEquals(CmsAddressListener::class, $postUpdate['class']);
        self::assertEquals(CmsAddressListener::class, $preUpdate['class']);
        self::assertEquals(CmsAddressListener::class, $postRemove['class']);
        self::assertEquals(CmsAddressListener::class, $preRemove['class']);
        self::assertEquals(CmsAddressListener::class, $postLoad['class']);
        self::assertEquals(CmsAddressListener::class, $preFlush['class']);

        self::assertEquals(Events::postPersist, $postPersist['method']);
        self::assertEquals(Events::prePersist, $prePersist['method']);
        self::assertEquals(Events::postUpdate, $postUpdate['method']);
        self::assertEquals(Events::preUpdate, $preUpdate['method']);
        self::assertEquals(Events::postRemove, $postRemove['method']);
        self::assertEquals(Events::preRemove, $preRemove['method']);
        self::assertEquals(Events::postLoad, $postLoad['method']);
        self::assertEquals(Events::preFlush, $preFlush['method']);
    }

    /**
     * @group DDC-2183
     */
    public function testSecondLevelCacheMapping() : void
    {
        $factory = $this->createClassMetadataFactory();
        $class   = $factory->getMetadataFor(City::class);

        self::assertNotNull($class->getCache());
        self::assertEquals(Mapping\CacheUsage::READ_ONLY, $class->getCache()->getUsage());
        self::assertEquals('doctrine_tests_models_cache_city', $class->getCache()->getRegion());

        self::assertArrayHasKey('state', iterator_to_array($class->getDeclaredPropertiesIterator()));

        $stateAssociation = $class->getProperty('state');

        self::assertNotNull($stateAssociation->getCache());
        self::assertEquals(Mapping\CacheUsage::READ_ONLY, $stateAssociation->getCache()->getUsage());
        self::assertEquals('doctrine_tests_models_cache_city__state', $stateAssociation->getCache()->getRegion());

        self::assertArrayHasKey('attractions', iterator_to_array($class->getDeclaredPropertiesIterator()));

        $attractionsAssociation = $class->getProperty('attractions');

        self::assertNotNull($attractionsAssociation->getCache());
        self::assertEquals(Mapping\CacheUsage::READ_ONLY, $attractionsAssociation->getCache()->getUsage());
        self::assertEquals('doctrine_tests_models_cache_city__attractions', $attractionsAssociation->getCache()->getRegion());
    }

    /**
     * @group DDC-2825
     * @group 881
     */
    public function testSchemaDefinitionViaExplicitTableSchemaAnnotationProperty() : void
    {
        $factory  = $this->createClassMetadataFactory();
        $metadata = $factory->getMetadataFor(ExplicitSchemaAndTable::class);

        self::assertSame('explicit_schema', $metadata->getSchemaName());
        self::assertSame('explicit_table', $metadata->getTableName());
    }

    /**
     * @group DDC-2825
     * @group 881
     */
    public function testSchemaDefinitionViaSchemaDefinedInTableNameInTableAnnotationProperty() : void
    {
        $factory  = $this->createClassMetadataFactory();
        $metadata = $factory->getMetadataFor(SchemaAndTableInTableName::class);

        self::assertSame('implicit_schema', $metadata->getSchemaName());
        self::assertSame('implicit_table', $metadata->getTableName());
    }

    /**
     * @group DDC-514
     * @group DDC-1015
     */
    public function testDiscriminatorColumnDefaultLength() : void
    {
        if (strpos(static::class, 'PHPMappingDriver') !== false) {
            $this->markTestSkipped('PHP Mapping Drivers have no defaults.');
        }

        $class = $this->createClassMetadata(SingleTableEntityNoDiscriminatorColumnMapping::class);

        self::assertEquals(255, $class->discriminatorColumn->getLength());

        $class = $this->createClassMetadata(SingleTableEntityIncompleteDiscriminatorColumnMapping::class);

        self::assertEquals(255, $class->discriminatorColumn->getLength());
    }

    /**
     * @group DDC-514
     * @group DDC-1015
     */
    public function testDiscriminatorColumnDefaultType() : void
    {
        if (strpos(static::class, 'PHPMappingDriver') !== false) {
            $this->markTestSkipped('PHP Mapping Drivers have no defaults.');
        }

        $class = $this->createClassMetadata(SingleTableEntityNoDiscriminatorColumnMapping::class);

        self::assertEquals('string', $class->discriminatorColumn->getTypeName());

        $class = $this->createClassMetadata(SingleTableEntityIncompleteDiscriminatorColumnMapping::class);

        self::assertEquals('string', $class->discriminatorColumn->getTypeName());
    }

    /**
     * @group DDC-514
     * @group DDC-1015
     */
    public function testDiscriminatorColumnDefaultName() : void
    {
        if (strpos(static::class, 'PHPMappingDriver') !== false) {
            $this->markTestSkipped('PHP Mapping Drivers have no defaults.');
        }

        $class = $this->createClassMetadata(SingleTableEntityNoDiscriminatorColumnMapping::class);

        self::assertEquals('dtype', $class->discriminatorColumn->getColumnName());

        $class = $this->createClassMetadata(SingleTableEntityIncompleteDiscriminatorColumnMapping::class);

        self::assertEquals('dtype', $class->discriminatorColumn->getColumnName());
    }
}

/**
 * @ORM\Entity
 * @ORM\HasLifecycleCallbacks
 * @ORM\Table(
 *  name="cms_users",
 *  uniqueConstraints={@ORM\UniqueConstraint(name="search_idx", columns={"name", "user_email"})},
 *  indexes={@ORM\Index(name="name_idx", columns={"name"}), @ORM\Index(columns={"user_email"})},
 *  options={"foo": "bar", "baz": {"key": "val"}}
 * )
 */
class User
{
    /**
     * @ORM\Id
     * @ORM\Column(type="integer", options={"foo": "bar", "unsigned": false})
     * @ORM\GeneratedValue(strategy="AUTO")
     * @ORM\SequenceGenerator(sequenceName="tablename_seq", allocationSize=100)
     */
    public $id;

    /** @ORM\Column(length=50, nullable=true, unique=true, options={"foo": "bar", "baz": {"key": "val"}, "fixed": false}) */
    public $name;

    /** @ORM\Column(name="user_email", columnDefinition="CHAR(32) NOT NULL") */
    public $email;

    /**
     * @ORM\OneToOne(targetEntity=Address::class, cascade={"remove"}, inversedBy="user")
     * @ORM\JoinColumn(onDelete="CASCADE")
     */
    public $address;

    /**
     * @ORM\OneToMany(targetEntity=Phonenumber::class, mappedBy="user", cascade={"persist"}, orphanRemoval=true)
     * @ORM\OrderBy({"number"="ASC"})
     */
    public $phonenumbers;

    /**
     * @ORM\ManyToMany(targetEntity=Group::class, cascade={"all"})
     * @ORM\JoinTable(name="cms_user_groups",
     *    joinColumns={@ORM\JoinColumn(name="user_id", referencedColumnName="id", nullable=false, unique=false)},
     *    inverseJoinColumns={@ORM\JoinColumn(name="group_id", referencedColumnName="id", columnDefinition="INT NULL")}
     * )
     */
    public $groups;

    /**
     * @ORM\Column(type="integer")
     * @ORM\Version
     */
    public $version;


    /**
     * @ORM\PrePersist
     */
    public function doStuffOnPrePersist()
    {
    }

    /**
     * @ORM\PrePersist
     */
    public function doOtherStuffOnPrePersistToo()
    {
    }

    /**
     * @ORM\PostPersist
     */
    public function doStuffOnPostPersist()
    {
    }

    public static function loadMetadata(ClassMetadata $metadata)
    {
        $tableMetadata = new Mapping\TableMetadata();

        $tableMetadata->setName('cms_users');
        $tableMetadata->addIndex(
            [
                'name'    => 'name_idx',
                'columns' => ['name'],
                'unique'  => false,
                'options' => [],
                'flags'   => [],
            ]
        );

        $tableMetadata->addIndex(
            [
                'name'    => null,
                'columns' => ['user_email'],
                'unique'  => false,
                'options' => [],
                'flags'   => [],
            ]
        );

        $tableMetadata->addUniqueConstraint(
            [
                'name'    => 'search_idx',
                'columns' => ['name', 'user_email'],
                'options' => [],
                'flags'   => [],
            ]
        );
        $tableMetadata->addOption('foo', 'bar');
        $tableMetadata->addOption('baz', ['key' => 'val']);

        $metadata->setTable($tableMetadata);
        $metadata->setInheritanceType(Mapping\InheritanceType::NONE);
        $metadata->setChangeTrackingPolicy(Mapping\ChangeTrackingPolicy::DEFERRED_IMPLICIT);

        $metadata->addLifecycleCallback('doStuffOnPrePersist', 'prePersist');
        $metadata->addLifecycleCallback('doOtherStuffOnPrePersistToo', 'prePersist');
        $metadata->addLifecycleCallback('doStuffOnPostPersist', 'postPersist');

        $metadata->setGeneratorDefinition(
            [
                'sequenceName'   => 'tablename_seq',
                'allocationSize' => 100,
            ]
        );

        $fieldMetadata = new Mapping\FieldMetadata('id');
        $fieldMetadata->setType(Type::getType('integer'));
        $fieldMetadata->setPrimaryKey(true);
        $fieldMetadata->setOptions(['foo' => 'bar', 'unsigned' => false]);

        $metadata->addProperty($fieldMetadata);

        $fieldMetadata = new Mapping\FieldMetadata('name');
        $fieldMetadata->setType(Type::getType('string'));
        $fieldMetadata->setLength(50);
        $fieldMetadata->setNullable(true);
        $fieldMetadata->setUnique(true);
        $fieldMetadata->setOptions(
            [
                'foo' => 'bar',
                'baz' => ['key' => 'val'],
                'fixed' => false,
            ]
        );

        $metadata->addProperty($fieldMetadata);

        $fieldMetadata = new Mapping\FieldMetadata('email');

        $fieldMetadata->setType(Type::getType('string'));
        $fieldMetadata->setColumnName('user_email');
        $fieldMetadata->setColumnDefinition('CHAR(32) NOT NULL');

        $metadata->addProperty($fieldMetadata);

        $fieldMetadata = new Mapping\VersionFieldMetadata('version');

        $fieldMetadata->setType(Type::getType('integer'));

        $metadata->addProperty($fieldMetadata);
        $metadata->setIdGeneratorType(Mapping\GeneratorType::AUTO);

        $joinColumns = [];

        $joinColumn = new Mapping\JoinColumnMetadata();

        $joinColumn->setColumnName('address_id');
        $joinColumn->setReferencedColumnName('id');
        $joinColumn->setOnDelete('CASCADE');

        $joinColumns[] = $joinColumn;

        $association = new Mapping\OneToOneAssociationMetadata('address');

        $association->setJoinColumns($joinColumns);
        $association->setTargetEntity(Address::class);
        $association->setInversedBy('user');
        $association->setCascade(['remove']);
        $association->setOrphanRemoval(false);

        $metadata->addProperty($association);

        $association = new Mapping\OneToManyAssociationMetadata('phonenumbers');

        $association->setTargetEntity(Phonenumber::class);
        $association->setMappedBy('user');
        $association->setCascade(['persist']);
        $association->setOrderBy(['number' => 'ASC']);
        $association->setOrphanRemoval(true);

        $metadata->addProperty($association);

        $joinTable = new Mapping\JoinTableMetadata();
        $joinTable->setName('cms_users_groups');

        $joinColumn = new Mapping\JoinColumnMetadata();

        $joinColumn->setColumnName('user_id');
        $joinColumn->setReferencedColumnName('id');
        $joinColumn->setNullable(false);
        $joinColumn->setUnique(false);

        $joinTable->addJoinColumn($joinColumn);

        $joinColumn = new Mapping\JoinColumnMetadata();

        $joinColumn->setColumnName('group_id');
        $joinColumn->setReferencedColumnName('id');
        $joinColumn->setColumnDefinition('INT NULL');

        $joinTable->addInverseJoinColumn($joinColumn);

        $association = new Mapping\ManyToManyAssociationMetadata('groups');

        $association->setJoinTable($joinTable);
        $association->setTargetEntity(Group::class);
        $association->setCascade(['remove', 'persist', 'refresh']);

        $metadata->addProperty($association);
    }
}

/**
 * @ORM\Entity
 * @ORM\InheritanceType("SINGLE_TABLE")
 * @ORM\DiscriminatorMap({"cat" = Cat::class, "dog" = Dog::class})
 * @ORM\DiscriminatorColumn(name="discr", length=32, type="string")
 */
abstract class Animal
{
    /**
     * @ORM\Id @ORM\Column(type="string") @ORM\GeneratedValue(strategy="CUSTOM")
     * @ORM\CustomIdGenerator(class=stdClass::class)
     */
    public $id;
}

/** @ORM\Entity */
class Cat extends Animal
{
}

/** @ORM\Entity */
class Dog extends Animal
{
}

/**
 * @ORM\Entity
 */
class DDC1170Entity
{
    /**
     * @param string $value
     */
    public function __construct($value = null)
    {
        $this->value = $value;
    }

    /**
     * @ORM\Id
     * @ORM\GeneratedValue(strategy="NONE")
     * @ORM\Column(type="integer", columnDefinition = "INT unsigned NOT NULL")
     */
    private $id;

    /** @ORM\Column(columnDefinition = "VARCHAR(255) NOT NULL") */
    private $value;

    /**
     * @return int
     */
    public function getId()
    {
        return $this->id;
    }

    /**
     * @return string
     */
    public function getValue()
    {
        return $this->value;
    }
}

/**
 * @ORM\Entity
 * @ORM\InheritanceType("SINGLE_TABLE")
 * @ORM\DiscriminatorMap({"ONE" = DDC807SubClasse1::class, "TWO" = DDC807SubClasse2::class})
 * @ORM\DiscriminatorColumn(name = "dtype", columnDefinition="ENUM('ONE','TWO')")
 */
class DDC807Entity
{
    /**
     * @ORM\Id
     * @ORM\Column(type="integer")
     * @ORM\GeneratedValue(strategy="NONE")
     */
    public $id;
}

class DDC807SubClasse1
{
}
class DDC807SubClasse2
{
}

class Address
{
}
class Phonenumber
{
}
class Group
{
}

/**
 * @ORM\Entity
 * @ORM\Table(indexes={@ORM\Index(columns={"content"}, flags={"fulltext"}, options={"where": "content IS NOT NULL"})})
 */
class Comment
{
    /** @ORM\Column(type="text") */
    private $content;
}

/**
 * @ORM\Entity
 * @ORM\InheritanceType("SINGLE_TABLE")
 * @ORM\DiscriminatorMap({
 *     "ONE" = Doctrine\Tests\ORM\Mapping\SingleTableEntityNoDiscriminatorColumnMappingSub1::class,
 *     "TWO" = Doctrine\Tests\ORM\Mapping\SingleTableEntityNoDiscriminatorColumnMappingSub2::class
 * })
 */
class SingleTableEntityNoDiscriminatorColumnMapping
{
    /**
     * @ORM\Id
     * @ORM\Column(type="integer")
     * @ORM\GeneratedValue(strategy="NONE")
     */
    public $id;
}

/**
 * @ORM\Entity
 */
class SingleTableEntityNoDiscriminatorColumnMappingSub1 extends SingleTableEntityNoDiscriminatorColumnMapping
{
}

/**
 * @ORM\Entity
 */
class SingleTableEntityNoDiscriminatorColumnMappingSub2 extends SingleTableEntityNoDiscriminatorColumnMapping
{
}

/**
 * @ORM\Entity
 * @ORM\InheritanceType("SINGLE_TABLE")
 * @ORM\DiscriminatorMap({
 *     "ONE" = Doctrine\Tests\ORM\Mapping\SingleTableEntityIncompleteDiscriminatorColumnMappingSub1::class,
 *     "TWO" = Doctrine\Tests\ORM\Mapping\SingleTableEntityIncompleteDiscriminatorColumnMappingSub2::class
 * })
 * @ORM\DiscriminatorColumn(name="dtype")
 */
class SingleTableEntityIncompleteDiscriminatorColumnMapping
{
    /**
     * @ORM\Id
     * @ORM\Column(type="integer")
     * @ORM\GeneratedValue(strategy="NONE")
     */
    public $id;
}

class SingleTableEntityIncompleteDiscriminatorColumnMappingSub1 extends SingleTableEntityIncompleteDiscriminatorColumnMapping
{
}

class SingleTableEntityIncompleteDiscriminatorColumnMappingSub2 extends SingleTableEntityIncompleteDiscriminatorColumnMapping
{
}
