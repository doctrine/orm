<?php

namespace Doctrine\Tests\ORM\Mapping;

use Doctrine\Common\Persistence\Mapping\RuntimeReflectionService;
use Doctrine\DBAL\Types\Type;
use Doctrine\ORM\EntityManager;
use Doctrine\ORM\Events;
use Doctrine\ORM\Mapping\ClassMetadata;
use Doctrine\ORM\Mapping\ClassMetadataFactory;
use Doctrine\ORM\Mapping\DefaultNamingStrategy;
use Doctrine\ORM\Mapping\MappingException;
use Doctrine\ORM\Mapping\UnderscoreNamingStrategy;
use Doctrine\Tests\Models\Cache\City;
use Doctrine\Tests\Models\CMS\CmsAddress;
use Doctrine\Tests\Models\CMS\CmsAddressListener;
use Doctrine\Tests\Models\CMS\CmsUser;
use Doctrine\Tests\Models\Company\CompanyContract;
use Doctrine\Tests\Models\Company\CompanyContractListener;
use Doctrine\Tests\Models\Company\CompanyFixContract;
use Doctrine\Tests\Models\Company\CompanyFlexContract;
use Doctrine\Tests\Models\Company\CompanyFlexUltraContract;
use Doctrine\Tests\Models\Company\CompanyFlexUltraContractListener;
use Doctrine\Tests\Models\Company\CompanyPerson;
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
use Doctrine\Tests\OrmTestCase;

abstract class AbstractMappingDriverTest extends OrmTestCase
{
    abstract protected function _loadDriver();

    public function createClassMetadata($entityClassName)
    {
        $mappingDriver = $this->_loadDriver();

        $class = new ClassMetadata($entityClassName);
        $class->initializeReflection(new RuntimeReflectionService());
        $mappingDriver->loadMetadataForClass($entityClassName, $class);

        return $class;
    }

    /**
     * @param \Doctrine\ORM\EntityManager $entityClassName
     * @return \Doctrine\ORM\Mapping\ClassMetadataFactory
     */
    protected function createClassMetadataFactory(EntityManager $em = null)
    {
        $driver     = $this->_loadDriver();
        $em         = $em ?: $this->_getTestEntityManager();
        $factory    = new ClassMetadataFactory();
        $em->getConfiguration()->setMetadataDriverImpl($driver);
        $factory->setEntityManager($em);

        return $factory;
    }

    /**
     * @param ClassMetadata $class
     */
    public function testEntityTableNameAndInheritance()
    {
        $class = $this->createClassMetadata(User::class);

        self::assertEquals('cms_users', $class->getTableName());
        self::assertEquals(ClassMetadata::INHERITANCE_TYPE_NONE, $class->inheritanceType);

        return $class;
    }

    /**
     * @depends testEntityTableNameAndInheritance
     * @param ClassMetadata $class
     */
    public function testEntityIndexes($class)
    {
        self::assertArrayHasKey('indexes', $class->table, 'ClassMetadata should have indexes key in table property.');
        self::assertEquals(
            [
                'name_idx' => ['columns' => ['name']],
                0 => ['columns' => ['user_email']]
            ],
            $class->table['indexes']
        );

        return $class;
    }

    public function testEntityIndexFlagsAndPartialIndexes()
    {
        $class = $this->createClassMetadata(Comment::class);

        self::assertEquals(
            [
                0 => [
                    'columns' => ['content'],
                    'flags' => ['fulltext'],
                    'options' => ['where' => 'content IS NOT NULL'],
                ]
            ],
            $class->table['indexes']
        );
    }

    /**
     * @depends testEntityTableNameAndInheritance
     * @param ClassMetadata $class
     */
    public function testEntityUniqueConstraints($class)
    {
        self::assertArrayHasKey('uniqueConstraints', $class->table,
            'ClassMetadata should have uniqueConstraints key in table property when Unique Constraints are set.');

        self::assertEquals(
            ["search_idx" => ["columns" => ["name", "user_email"], 'options' => ['where' => 'name IS NOT NULL']]],
            $class->table['uniqueConstraints']
        );

        return $class;
    }

    /**
     * @depends testEntityTableNameAndInheritance
     * @param ClassMetadata $class
     */
    public function testEntityOptions($class)
    {
        self::assertArrayHasKey('options', $class->table, 'ClassMetadata should have options key in table property.');

        self::assertEquals(
            ['foo' => 'bar', 'baz' => ['key' => 'val']],
            $class->table['options']
        );

        return $class;
    }

    /**
     * @depends testEntityOptions
     * @param ClassMetadata $class
     */
    public function testEntitySequence($class)
    {
        self::assertInternalType('array', $class->sequenceGeneratorDefinition, 'No Sequence Definition set on this driver.');
        self::assertEquals(
            [
                'sequenceName' => 'tablename_seq',
                'allocationSize' => 100,
                'initialValue' => 1,
            ],
            $class->sequenceGeneratorDefinition
        );
    }

    public function testEntityCustomGenerator()
    {
        $class = $this->createClassMetadata(Animal::class);

        self::assertEquals(ClassMetadata::GENERATOR_TYPE_CUSTOM, $class->generatorType, "Generator Type");
        self::assertEquals(
            ["class" => "stdClass"],
            $class->customGeneratorDefinition,
            "Custom Generator Definition");
    }


    /**
     * @depends testEntityTableNameAndInheritance
     * @param ClassMetadata $class
     */
    public function testFieldMappings($class)
    {
        self::assertEquals(4, count($class->fieldMappings));
        self::assertTrue(isset($class->fieldMappings['id']));
        self::assertTrue(isset($class->fieldMappings['name']));
        self::assertTrue(isset($class->fieldMappings['email']));
        self::assertTrue(isset($class->fieldMappings['version']));

        return $class;
    }

    /**
     * @depends testFieldMappings
     * @param ClassMetadata $class
     */
    public function testVersionedField($class)
    {
        self::assertTrue($class->isVersioned);
        self::assertEquals("version", $class->versionField);

        self::assertFalse(isset($class->fieldMappings['version']['version']));
    }

    /**
     * @depends testEntityTableNameAndInheritance
     * @param ClassMetadata $class
     */
    public function testFieldMappingsColumnNames($class)
    {
        self::assertEquals("id", $class->fieldMappings['id']['columnName']);
        self::assertEquals("name", $class->fieldMappings['name']['columnName']);
        self::assertEquals("user_email", $class->fieldMappings['email']['columnName']);

        return $class;
    }

    /**
     * @depends testEntityTableNameAndInheritance
     * @param ClassMetadata $class
     */
    public function testStringFieldMappings($class)
    {
        self::assertEquals('string', $class->fieldMappings['name']['type']->getName());
        self::assertEquals(50, $class->fieldMappings['name']['length']);
        self::assertTrue($class->fieldMappings['name']['nullable']);
        self::assertTrue($class->fieldMappings['name']['unique']);

        return $class;
    }

    /**
     * @depends testEntityTableNameAndInheritance
     *
     * @param ClassMetadata $class
     *
     * @return ClassMetadata
     */
    public function testFieldOptions(ClassMetadata $class)
    {
        $expected = ['foo' => 'bar', 'baz' => ['key' => 'val'], 'fixed' => false];
        self::assertEquals($expected, $class->fieldMappings['name']['options']);

        return $class;
    }

    /**
     * @depends testEntityTableNameAndInheritance
     * @param ClassMetadata $class
     */
    public function testIdFieldOptions($class)
    {
        self::assertEquals(['foo' => 'bar', 'unsigned' => false], $class->fieldMappings['id']['options']);

        return $class;
    }

    /**
     * @depends testFieldMappings
     * @param ClassMetadata $class
     */
    public function testIdentifier($class)
    {
        self::assertEquals(['id'], $class->identifier);
        self::assertEquals('integer', $class->fieldMappings['id']['type']->getName());
        self::assertEquals(ClassMetadata::GENERATOR_TYPE_AUTO, $class->generatorType, "ID-Generator is not ClassMetadata::GENERATOR_TYPE_AUTO");

        return $class;
    }

    /**
     * @group #6129
     *
     * @return ClassMetadata
     */
    public function testBooleanValuesForOptionIsSetCorrectly()
    {
        $class = $this->createClassMetadata(User::class);

        $idOptions = $class->getProperty('id')->getOptions();
        $nameOptions = $class->getProperty('name')->getOptions();

        self::assertInternalType('bool', $idOptions['unsigned']);
        self::assertFalse($idOptions['unsigned']);
        self::assertInternalType('bool', $nameOptions['fixed']);
        self::assertFalse($nameOptions['fixed']);

        return $class;
    }

    /**
     * @depends testIdentifier
     * @param ClassMetadata $class
     */
    public function testAssociations($class)
    {
        self::assertEquals(3, count($class->associationMappings));

        return $class;
    }

    /**
     * @depends testAssociations
     * @param ClassMetadata $class
     */
    public function testOwningOneToOneAssociation($class)
    {
        self::assertTrue(isset($class->associationMappings['address']));
        self::assertTrue($class->associationMappings['address']['isOwningSide']);
        self::assertEquals('user', $class->associationMappings['address']['inversedBy']);
        // Check cascading
        self::assertTrue($class->associationMappings['address']['isCascadeRemove']);
        self::assertFalse($class->associationMappings['address']['isCascadePersist']);
        self::assertFalse($class->associationMappings['address']['isCascadeRefresh']);
        self::assertFalse($class->associationMappings['address']['isCascadeDetach']);
        self::assertFalse($class->associationMappings['address']['isCascadeMerge']);

        return $class;
    }

    /**
     * @depends testOwningOneToOneAssociation
     * @param ClassMetadata $class
     */
    public function testInverseOneToManyAssociation($class)
    {
        self::assertTrue(isset($class->associationMappings['phonenumbers']));
        self::assertFalse($class->associationMappings['phonenumbers']['isOwningSide']);
        self::assertTrue($class->associationMappings['phonenumbers']['isCascadePersist']);
        self::assertTrue($class->associationMappings['phonenumbers']['isCascadeRemove']);
        self::assertFalse($class->associationMappings['phonenumbers']['isCascadeRefresh']);
        self::assertFalse($class->associationMappings['phonenumbers']['isCascadeDetach']);
        self::assertFalse($class->associationMappings['phonenumbers']['isCascadeMerge']);
        self::assertTrue($class->associationMappings['phonenumbers']['orphanRemoval']);

        // Test Order By
        self::assertEquals(['number' => 'ASC'], $class->associationMappings['phonenumbers']['orderBy']);

        return $class;
    }

    /**
     * @depends testInverseOneToManyAssociation
     * @param ClassMetadata $class
     */
    public function testManyToManyAssociationWithCascadeAll($class)
    {
        self::assertTrue(isset($class->associationMappings['groups']));
        self::assertTrue($class->associationMappings['groups']['isOwningSide']);
        // Make sure that cascade-all works as expected
        self::assertTrue($class->associationMappings['groups']['isCascadeRemove']);
        self::assertTrue($class->associationMappings['groups']['isCascadePersist']);
        self::assertTrue($class->associationMappings['groups']['isCascadeRefresh']);
        self::assertTrue($class->associationMappings['groups']['isCascadeDetach']);
        self::assertTrue($class->associationMappings['groups']['isCascadeMerge']);

        self::assertFalse(isset($class->associationMappings['groups']['orderBy']));

        return $class;
    }

    /**
     * @depends testManyToManyAssociationWithCascadeAll
     * @param ClassMetadata $class
     */
    public function testLifecycleCallbacks($class)
    {
        self::assertEquals(count($class->lifecycleCallbacks), 2);
        self::assertEquals($class->lifecycleCallbacks['prePersist'][0], 'doStuffOnPrePersist');
        self::assertEquals($class->lifecycleCallbacks['postPersist'][0], 'doStuffOnPostPersist');

        return $class;
    }

    /**
     * @depends testManyToManyAssociationWithCascadeAll
     * @param ClassMetadata $class
     */
    public function testLifecycleCallbacksSupportMultipleMethodNames($class)
    {
        self::assertEquals(count($class->lifecycleCallbacks['prePersist']), 2);
        self::assertEquals($class->lifecycleCallbacks['prePersist'][1], 'doOtherStuffOnPrePersistToo');

        return $class;
    }

    /**
     * @depends testLifecycleCallbacksSupportMultipleMethodNames
     * @param ClassMetadata $class
     */
    public function testJoinColumnUniqueAndNullable($class)
    {
        // Non-Nullability of Join Column
        self::assertFalse($class->associationMappings['groups']['joinTable']['joinColumns'][0]['nullable']);
        self::assertFalse($class->associationMappings['groups']['joinTable']['joinColumns'][0]['unique']);

        return $class;
    }

    /**
     * @depends testJoinColumnUniqueAndNullable
     * @param ClassMetadata $class
     */
    public function testColumnDefinition($class)
    {
        self::assertEquals("CHAR(32) NOT NULL", $class->fieldMappings['email']['columnDefinition']);
        self::assertEquals("INT NULL", $class->associationMappings['groups']['joinTable']['inverseJoinColumns'][0]['columnDefinition']);

        return $class;
    }

    /**
     * @depends testColumnDefinition
     * @param ClassMetadata $class
     */
    public function testJoinColumnOnDelete($class)
    {
        self::assertEquals('CASCADE', $class->associationMappings['address']['joinColumns'][0]['onDelete']);

        return $class;
    }

    /**
     * @group DDC-514
     */
    public function testDiscriminatorColumnDefaults()
    {
        if (strpos(get_class($this), 'PHPMappingDriver') !== false) {
            $this->markTestSkipped('PHP Mapping Drivers have no defaults.');
        }

        $class = $this->createClassMetadata(Animal::class);

        self::assertEquals(
            [
                'name'             => 'discr',
                'type'             => Type::getType('string'),
                'length'           => '32',
                'fieldName'        => 'discr',
                'columnDefinition' => null,
                'tableName'        => 'Animal',
            ],
            $class->discriminatorColumn
        );
    }

    /**
     * @group DDC-869
     */
    public function testMappedSuperclassWithRepository()
    {
        $em         = $this->_getTestEntityManager();
        $factory    = $this->createClassMetadataFactory($em);
        $class = $factory->getMetadataFor(DDC869CreditCardPayment::class);

        self::assertTrue(isset($class->fieldMappings['id']));
        self::assertTrue(isset($class->fieldMappings['value']));
        self::assertTrue(isset($class->fieldMappings['creditCardNumber']));
        self::assertEquals($class->customRepositoryClassName, DDC869PaymentRepository::class);
        self::assertInstanceOf(DDC869PaymentRepository::class, $em->getRepository(DDC869CreditCardPayment::class));
        self::assertTrue($em->getRepository(DDC869ChequePayment::class)->isTrue());

        $class = $factory->getMetadataFor(DDC869ChequePayment::class);

        self::assertTrue(isset($class->fieldMappings['id']));
        self::assertTrue(isset($class->fieldMappings['value']));
        self::assertTrue(isset($class->fieldMappings['serialNumber']));
        self::assertEquals($class->customRepositoryClassName, DDC869PaymentRepository::class);
        self::assertInstanceOf(DDC869PaymentRepository::class, $em->getRepository(DDC869ChequePayment::class));
        self::assertTrue($em->getRepository(DDC869ChequePayment::class)->isTrue());
    }

    /**
     * @group DDC-1476
     */
    public function testDefaultFieldType()
    {
        $factory    = $this->createClassMetadataFactory();
        $class      = $factory->getMetadataFor(DDC1476EntityWithDefaultFieldType::class);


        self::assertArrayHasKey('id', $class->fieldMappings);
        self::assertArrayHasKey('name', $class->fieldMappings);

        self::assertArrayHasKey('type', $class->fieldMappings['id']);
        self::assertArrayHasKey('type', $class->fieldMappings['name']);

        self::assertEquals('string', $class->fieldMappings['id']['type']->getName());
        self::assertEquals('string', $class->fieldMappings['name']['type']->getName());

        self::assertArrayHasKey('fieldName', $class->fieldMappings['id']);
        self::assertArrayHasKey('fieldName', $class->fieldMappings['name']);

        self::assertEquals('id', $class->fieldMappings['id']['fieldName']);
        self::assertEquals('name', $class->fieldMappings['name']['fieldName']);

        self::assertArrayHasKey('columnName', $class->fieldMappings['id']);
        self::assertArrayHasKey('columnName', $class->fieldMappings['name']);

        self::assertEquals('id', $class->fieldMappings['id']['columnName']);
        self::assertEquals('name', $class->fieldMappings['name']['columnName']);

        self::assertEquals(ClassMetadata::GENERATOR_TYPE_NONE, $class->generatorType);
    }

    /**
     * @group DDC-1170
     */
    public function testIdentifierColumnDefinition()
    {
        $class = $this->createClassMetadata(DDC1170Entity::class);

        self::assertArrayHasKey('id', $class->fieldMappings);
        self::assertArrayHasKey('value', $class->fieldMappings);

        self::assertArrayHasKey('columnDefinition', $class->fieldMappings['id']);
        self::assertArrayHasKey('columnDefinition', $class->fieldMappings['value']);

        self::assertEquals("INT unsigned NOT NULL", $class->fieldMappings['id']['columnDefinition']);
        self::assertEquals("VARCHAR(255) NOT NULL", $class->fieldMappings['value']['columnDefinition']);
    }

    /**
     * @group DDC-559
     */
    public function testNamingStrategy()
    {
        $em         = $this->_getTestEntityManager();
        $factory    = $this->createClassMetadataFactory($em);

        self::assertInstanceOf(DefaultNamingStrategy::class, $em->getConfiguration()->getNamingStrategy());
        $em->getConfiguration()->setNamingStrategy(new UnderscoreNamingStrategy(CASE_UPPER));
        self::assertInstanceOf(UnderscoreNamingStrategy::class, $em->getConfiguration()->getNamingStrategy());

        $class = $factory->getMetadataFor(DDC1476EntityWithDefaultFieldType::class);

        self::assertEquals('ID', $class->getColumnName('id'));
        self::assertEquals('NAME', $class->getColumnName('name'));
        self::assertEquals('DDC1476ENTITY_WITH_DEFAULT_FIELD_TYPE', $class->table['name']);
    }

    /**
     * @group DDC-807
     * @group DDC-553
     */
    public function testDiscriminatorColumnDefinition()
    {
        $class = $this->createClassMetadata(DDC807Entity::class);

        self::assertArrayHasKey('columnDefinition', $class->discriminatorColumn);
        self::assertArrayHasKey('name', $class->discriminatorColumn);

        self::assertEquals("ENUM('ONE','TWO')", $class->discriminatorColumn['columnDefinition']);
        self::assertEquals("dtype", $class->discriminatorColumn['name']);
    }

    /**
     * @group DDC-889
     */
    public function testInvalidEntityOrMappedSuperClassShouldMentionParentClasses()
    {
        $this->expectException(MappingException::class);
        $this->expectExceptionMessage('Class "Doctrine\Tests\Models\DDC889\DDC889Class" sub class of "Doctrine\Tests\Models\DDC889\DDC889SuperClass" is not a valid entity or mapped super class.');

        $this->createClassMetadata(DDC889Class::class);
    }

    /**
     * @group DDC-889
     */
    public function testIdentifierRequiredShouldMentionParentClasses()
    {
        $factory = $this->createClassMetadataFactory();

        $this->expectException(MappingException::class);
        $this->expectExceptionMessage('No identifier/primary key specified for Entity "Doctrine\Tests\Models\DDC889\DDC889Entity" sub class of "Doctrine\Tests\Models\DDC889\DDC889SuperClass". Every Entity must have an identifier/primary key.');

        $factory->getMetadataFor(DDC889Entity::class);
    }

    public function testNamedQuery()
    {
        $driver = $this->_loadDriver();
        $class = $this->createClassMetadata(User::class);

        self::assertCount(1, $class->getNamedQueries(), sprintf("Named queries not processed correctly by driver %s", get_class($driver)));
    }

    /**
     * @group DDC-1663
     */
    public function testNamedNativeQuery()
    {

        $class = $this->createClassMetadata(CmsAddress::class);

        //named native query
        self::assertCount(3, $class->namedNativeQueries);
        self::assertArrayHasKey('find-all', $class->namedNativeQueries);
        self::assertArrayHasKey('find-by-id', $class->namedNativeQueries);

        $findAllQuery = $class->getNamedNativeQuery('find-all');
        self::assertEquals('find-all', $findAllQuery['name']);
        self::assertEquals('mapping-find-all', $findAllQuery['resultSetMapping']);
        self::assertEquals('SELECT id, country, city FROM cms_addresses', $findAllQuery['query']);

        $findByIdQuery = $class->getNamedNativeQuery('find-by-id');
        self::assertEquals('find-by-id', $findByIdQuery['name']);
        self::assertEquals(CmsAddress::class,$findByIdQuery['resultClass']);
        self::assertEquals('SELECT * FROM cms_addresses WHERE id = ?',  $findByIdQuery['query']);

        $countQuery = $class->getNamedNativeQuery('count');
        self::assertEquals('count', $countQuery['name']);
        self::assertEquals('mapping-count', $countQuery['resultSetMapping']);
        self::assertEquals('SELECT COUNT(*) AS count FROM cms_addresses',  $countQuery['query']);

        // result set mapping
        self::assertCount(3, $class->sqlResultSetMappings);
        self::assertArrayHasKey('mapping-count', $class->sqlResultSetMappings);
        self::assertArrayHasKey('mapping-find-all', $class->sqlResultSetMappings);
        self::assertArrayHasKey('mapping-without-fields', $class->sqlResultSetMappings);

        $findAllMapping = $class->getSqlResultSetMapping('mapping-find-all');
        self::assertEquals('mapping-find-all', $findAllMapping['name']);
        self::assertEquals(CmsAddress::class, $findAllMapping['entities'][0]['entityClass']);
        self::assertEquals(['name'=>'id','column'=>'id'], $findAllMapping['entities'][0]['fields'][0]);
        self::assertEquals(['name'=>'city','column'=>'city'], $findAllMapping['entities'][0]['fields'][1]);
        self::assertEquals(['name'=>'country','column'=>'country'], $findAllMapping['entities'][0]['fields'][2]);

        $withoutFieldsMapping = $class->getSqlResultSetMapping('mapping-without-fields');
        self::assertEquals('mapping-without-fields', $withoutFieldsMapping['name']);
        self::assertEquals(CmsAddress::class, $withoutFieldsMapping['entities'][0]['entityClass']);
        self::assertEquals([], $withoutFieldsMapping['entities'][0]['fields']);

        $countMapping = $class->getSqlResultSetMapping('mapping-count');
        self::assertEquals('mapping-count', $countMapping['name']);
        self::assertEquals(['name'=>'count'], $countMapping['columns'][0]);

    }

    /**
     * @group DDC-1663
     */
    public function testSqlResultSetMapping()
    {
        $userMetadata   = $this->createClassMetadata(CmsUser::class);
        $personMetadata = $this->createClassMetadata(CompanyPerson::class);

        // user asserts
        self::assertCount(4, $userMetadata->getSqlResultSetMappings());

        $mapping = $userMetadata->getSqlResultSetMapping('mappingJoinedAddress');
        self::assertEquals([],$mapping['columns']);
        self::assertEquals('mappingJoinedAddress', $mapping['name']);
        self::assertNull($mapping['entities'][0]['discriminatorColumn']);
        self::assertEquals(['name'=>'id','column'=>'id'],                   $mapping['entities'][0]['fields'][0]);
        self::assertEquals(['name'=>'name','column'=>'name'],               $mapping['entities'][0]['fields'][1]);
        self::assertEquals(['name'=>'status','column'=>'status'],           $mapping['entities'][0]['fields'][2]);
        self::assertEquals(['name'=>'address.zip','column'=>'zip'],         $mapping['entities'][0]['fields'][3]);
        self::assertEquals(['name'=>'address.city','column'=>'city'],       $mapping['entities'][0]['fields'][4]);
        self::assertEquals(['name'=>'address.country','column'=>'country'], $mapping['entities'][0]['fields'][5]);
        self::assertEquals(['name'=>'address.id','column'=>'a_id'],         $mapping['entities'][0]['fields'][6]);
        self::assertEquals($userMetadata->name,                             $mapping['entities'][0]['entityClass']);

        $mapping = $userMetadata->getSqlResultSetMapping('mappingJoinedPhonenumber');
        self::assertEquals([],$mapping['columns']);
        self::assertEquals('mappingJoinedPhonenumber', $mapping['name']);
        self::assertNull($mapping['entities'][0]['discriminatorColumn']);
        self::assertEquals(['name'=>'id','column'=>'id'],                             $mapping['entities'][0]['fields'][0]);
        self::assertEquals(['name'=>'name','column'=>'name'],                         $mapping['entities'][0]['fields'][1]);
        self::assertEquals(['name'=>'status','column'=>'status'],                     $mapping['entities'][0]['fields'][2]);
        self::assertEquals(['name'=>'phonenumbers.phonenumber','column'=>'number'],   $mapping['entities'][0]['fields'][3]);
        self::assertEquals($userMetadata->name,                                       $mapping['entities'][0]['entityClass']);

        $mapping = $userMetadata->getSqlResultSetMapping('mappingUserPhonenumberCount');
        self::assertEquals(['name'=>'numphones'],$mapping['columns'][0]);
        self::assertEquals('mappingUserPhonenumberCount', $mapping['name']);
        self::assertNull($mapping['entities'][0]['discriminatorColumn']);
        self::assertEquals(['name'=>'id','column'=>'id'],         $mapping['entities'][0]['fields'][0]);
        self::assertEquals(['name'=>'name','column'=>'name'],     $mapping['entities'][0]['fields'][1]);
        self::assertEquals(['name'=>'status','column'=>'status'], $mapping['entities'][0]['fields'][2]);
        self::assertEquals($userMetadata->name,                   $mapping['entities'][0]['entityClass']);

        $mapping = $userMetadata->getSqlResultSetMapping('mappingMultipleJoinsEntityResults');
        self::assertEquals(['name'=>'numphones'],$mapping['columns'][0]);
        self::assertEquals('mappingMultipleJoinsEntityResults', $mapping['name']);
        self::assertNull($mapping['entities'][0]['discriminatorColumn']);
        self::assertEquals(['name'=>'id','column'=>'u_id'],           $mapping['entities'][0]['fields'][0]);
        self::assertEquals(['name'=>'name','column'=>'u_name'],       $mapping['entities'][0]['fields'][1]);
        self::assertEquals(['name'=>'status','column'=>'u_status'],   $mapping['entities'][0]['fields'][2]);
        self::assertEquals($userMetadata->name,                       $mapping['entities'][0]['entityClass']);
        self::assertNull($mapping['entities'][1]['discriminatorColumn']);
        self::assertEquals(['name'=>'id','column'=>'a_id'],           $mapping['entities'][1]['fields'][0]);
        self::assertEquals(['name'=>'zip','column'=>'a_zip'],         $mapping['entities'][1]['fields'][1]);
        self::assertEquals(['name'=>'country','column'=>'a_country'], $mapping['entities'][1]['fields'][2]);
        self::assertEquals(CmsAddress::class,                         $mapping['entities'][1]['entityClass']);

        //person asserts
        self::assertCount(1, $personMetadata->getSqlResultSetMappings());

        $mapping = $personMetadata->getSqlResultSetMapping('mappingFetchAll');
        self::assertEquals([],$mapping['columns']);
        self::assertEquals('mappingFetchAll', $mapping['name']);
        self::assertEquals('discriminator',                   $mapping['entities'][0]['discriminatorColumn']);
        self::assertEquals(['name'=>'id','column'=>'id'],     $mapping['entities'][0]['fields'][0]);
        self::assertEquals(['name'=>'name','column'=>'name'], $mapping['entities'][0]['fields'][1]);
        self::assertEquals($personMetadata->name,             $mapping['entities'][0]['entityClass']);
    }

    /*
     * @group DDC-964
     */
    public function testAssociationOverridesMapping()
    {
        $factory        = $this->createClassMetadataFactory();
        $adminMetadata  = $factory->getMetadataFor(DDC964Admin::class);
        $guestMetadata  = $factory->getMetadataFor(DDC964Guest::class);

        // assert groups association mappings
        self::assertArrayHasKey('groups', $guestMetadata->associationMappings);
        self::assertArrayHasKey('groups', $adminMetadata->associationMappings);

        $guestGroups = $guestMetadata->associationMappings['groups'];
        $adminGroups = $adminMetadata->associationMappings['groups'];

        // assert not override attributes
        self::assertEquals($guestGroups['fieldName'], $adminGroups['fieldName']);
        self::assertEquals($guestGroups['type'], $adminGroups['type']);
        self::assertEquals($guestGroups['mappedBy'], $adminGroups['mappedBy']);
        self::assertEquals($guestGroups['inversedBy'], $adminGroups['inversedBy']);
        self::assertEquals($guestGroups['isOwningSide'], $adminGroups['isOwningSide']);
        self::assertEquals($guestGroups['fetch'], $adminGroups['fetch']);
        self::assertEquals($guestGroups['isCascadeRemove'], $adminGroups['isCascadeRemove']);
        self::assertEquals($guestGroups['isCascadePersist'], $adminGroups['isCascadePersist']);
        self::assertEquals($guestGroups['isCascadeRefresh'], $adminGroups['isCascadeRefresh']);
        self::assertEquals($guestGroups['isCascadeMerge'], $adminGroups['isCascadeMerge']);
        self::assertEquals($guestGroups['isCascadeDetach'], $adminGroups['isCascadeDetach']);

         // assert not override attributes
        self::assertEquals('ddc964_users_groups', $guestGroups['joinTable']['name']);
        self::assertEquals('user_id', $guestGroups['joinTable']['joinColumns'][0]['name']);
        self::assertEquals('group_id', $guestGroups['joinTable']['inverseJoinColumns'][0]['name']);

        self::assertEquals(['user_id'=>'id'], $guestGroups['relationToSourceKeyColumns']);
        self::assertEquals(['group_id'=>'id'], $guestGroups['relationToTargetKeyColumns']);
        self::assertEquals(['user_id','group_id'], $guestGroups['joinTableColumns']);


        self::assertEquals('ddc964_users_admingroups', $adminGroups['joinTable']['name']);
        self::assertEquals('adminuser_id', $adminGroups['joinTable']['joinColumns'][0]['name']);
        self::assertEquals('admingroup_id', $adminGroups['joinTable']['inverseJoinColumns'][0]['name']);

        self::assertEquals(['adminuser_id'=>'id'], $adminGroups['relationToSourceKeyColumns']);
        self::assertEquals(['admingroup_id'=>'id'], $adminGroups['relationToTargetKeyColumns']);
        self::assertEquals(['adminuser_id','admingroup_id'], $adminGroups['joinTableColumns']);


        // assert address association mappings
        self::assertArrayHasKey('address', $guestMetadata->associationMappings);
        self::assertArrayHasKey('address', $adminMetadata->associationMappings);

        $guestAddress = $guestMetadata->associationMappings['address'];
        $adminAddress = $adminMetadata->associationMappings['address'];

        // assert not override attributes
        self::assertEquals($guestAddress['fieldName'], $adminAddress['fieldName']);
        self::assertEquals($guestAddress['type'], $adminAddress['type']);
        self::assertEquals($guestAddress['mappedBy'], $adminAddress['mappedBy']);
        self::assertEquals($guestAddress['inversedBy'], $adminAddress['inversedBy']);
        self::assertEquals($guestAddress['isOwningSide'], $adminAddress['isOwningSide']);
        self::assertEquals($guestAddress['fetch'], $adminAddress['fetch']);
        self::assertEquals($guestAddress['isCascadeRemove'], $adminAddress['isCascadeRemove']);
        self::assertEquals($guestAddress['isCascadePersist'], $adminAddress['isCascadePersist']);
        self::assertEquals($guestAddress['isCascadeRefresh'], $adminAddress['isCascadeRefresh']);
        self::assertEquals($guestAddress['isCascadeMerge'], $adminAddress['isCascadeMerge']);
        self::assertEquals($guestAddress['isCascadeDetach'], $adminAddress['isCascadeDetach']);

        // assert override
        self::assertEquals('address_id', $guestAddress['joinColumns'][0]['name']);
        self::assertEquals(['address_id'=>'id'], $guestAddress['sourceToTargetKeyColumns']);
        self::assertEquals(['address_id'=>'address_id'], $guestAddress['joinColumnFieldNames']);
        self::assertEquals(['id'=>'address_id'], $guestAddress['targetToSourceKeyColumns']);


        self::assertEquals('adminaddress_id', $adminAddress['joinColumns'][0]['name']);
        self::assertEquals(['adminaddress_id'=>'id'], $adminAddress['sourceToTargetKeyColumns']);
        self::assertEquals(['adminaddress_id'=>'adminaddress_id'], $adminAddress['joinColumnFieldNames']);
        self::assertEquals(['id'=>'adminaddress_id'], $adminAddress['targetToSourceKeyColumns']);
    }

    /*
     * @group DDC-3579
     */
    public function testInversedByOverrideMapping()
    {
        $factory        = $this->createClassMetadataFactory();
        $adminMetadata  = $factory->getMetadataFor(DDC3579Admin::class);

        // assert groups association mappings
        self::assertArrayHasKey('groups', $adminMetadata->associationMappings);
        $adminGroups = $adminMetadata->associationMappings['groups'];

        // assert override
        self::assertEquals('admins', $adminGroups['inversedBy']);
    }

    /**
     * @group DDC-5934
     */
    public function testFetchOverrideMapping()
    {
        // check override metadata
        $contractMetadata = $this->createClassMetadataFactory()->getMetadataFor(DDC5934Contract::class);

        $this->assertArrayHasKey('members', $contractMetadata->associationMappings);
        $this->assertSame(ClassMetadata::FETCH_EXTRA_LAZY, $contractMetadata->associationMappings['members']['fetch']);
    }

    /**
     * @group DDC-964
     */
    public function testAttributeOverridesMapping()
    {
        $factory       = $this->createClassMetadataFactory();
        $guestMetadata = $factory->getMetadataFor(DDC964Guest::class);
        $adminMetadata = $factory->getMetadataFor(DDC964Admin::class);

        self::assertTrue($adminMetadata->fieldMappings['id']['id']);
        self::assertEquals('id', $adminMetadata->fieldMappings['id']['fieldName']);
        self::assertEquals('user_id', $adminMetadata->fieldMappings['id']['columnName']);
        self::assertEquals(['user_id'=>'id','user_name'=>'name'], $adminMetadata->fieldNames);
        self::assertEquals(150, $adminMetadata->fieldMappings['id']['length']);


        self::assertEquals('name', $adminMetadata->fieldMappings['name']['fieldName']);
        self::assertEquals('user_name', $adminMetadata->fieldMappings['name']['columnName']);
        self::assertEquals(250, $adminMetadata->fieldMappings['name']['length']);
        self::assertTrue($adminMetadata->fieldMappings['name']['nullable']);
        self::assertFalse($adminMetadata->fieldMappings['name']['unique']);


        self::assertTrue($guestMetadata->fieldMappings['id']['id']);
        self::assertEquals('guest_id', $guestMetadata->fieldMappings['id']['columnName']);
        self::assertEquals('id', $guestMetadata->fieldMappings['id']['fieldName']);
        self::assertEquals(['guest_id'=>'id','guest_name'=>'name'], $guestMetadata->fieldNames);
        self::assertEquals(140, $guestMetadata->fieldMappings['id']['length']);

        self::assertEquals('name', $guestMetadata->fieldMappings['name']['fieldName']);
        self::assertEquals('guest_name', $guestMetadata->fieldMappings['name']['columnName']);
        self::assertEquals(240, $guestMetadata->fieldMappings['name']['length']);
        self::assertFalse($guestMetadata->fieldMappings['name']['nullable']);
        self::assertTrue($guestMetadata->fieldMappings['name']['unique']);
    }

    /**
     * @group DDC-1955
     */
    public function testEntityListeners()
    {
        $em         = $this->_getTestEntityManager();
        $factory    = $this->createClassMetadataFactory($em);
        $superClass = $factory->getMetadataFor(CompanyContract::class);
        $flexClass  = $factory->getMetadataFor(CompanyFixContract::class);
        $fixClass   = $factory->getMetadataFor(CompanyFlexContract::class);
        $ultraClass = $factory->getMetadataFor(CompanyFlexUltraContract::class);

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
    public function testEntityListenersOverride()
    {
        $em         = $this->_getTestEntityManager();
        $factory    = $this->createClassMetadataFactory($em);
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
    public function testEntityListenersNamingConvention()
    {
        $em         = $this->_getTestEntityManager();
        $factory    = $this->createClassMetadataFactory($em);
        $metadata   = $factory->getMetadataFor(CmsAddress::class);

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
    public function testSecondLevelCacheMapping()
    {
        $em      = $this->_getTestEntityManager();
        $factory = $this->createClassMetadataFactory($em);
        $class   = $factory->getMetadataFor(City::class);
        self::assertArrayHasKey('usage', $class->cache);
        self::assertArrayHasKey('region', $class->cache);
        self::assertEquals(ClassMetadata::CACHE_USAGE_READ_ONLY, $class->cache['usage']);
        self::assertEquals('doctrine_tests_models_cache_city', $class->cache['region']);

        self::assertArrayHasKey('state', $class->associationMappings);
        self::assertArrayHasKey('cache', $class->associationMappings['state']);
        self::assertArrayHasKey('usage', $class->associationMappings['state']['cache']);
        self::assertArrayHasKey('region', $class->associationMappings['state']['cache']);
        self::assertEquals(ClassMetadata::CACHE_USAGE_READ_ONLY, $class->associationMappings['state']['cache']['usage']);
        self::assertEquals('doctrine_tests_models_cache_city__state', $class->associationMappings['state']['cache']['region']);

        self::assertArrayHasKey('attractions', $class->associationMappings);
        self::assertArrayHasKey('cache', $class->associationMappings['attractions']);
        self::assertArrayHasKey('usage', $class->associationMappings['attractions']['cache']);
        self::assertArrayHasKey('region', $class->associationMappings['attractions']['cache']);
        self::assertEquals(ClassMetadata::CACHE_USAGE_READ_ONLY, $class->associationMappings['attractions']['cache']['usage']);
        self::assertEquals('doctrine_tests_models_cache_city__attractions', $class->associationMappings['attractions']['cache']['region']);
    }

    /**
     * @group DDC-2825
     * @group 881
     */
    public function testSchemaDefinitionViaExplicitTableSchemaAnnotationProperty()
    {
        /* @var $metadata \Doctrine\ORM\Mapping\ClassMetadata */
        $metadata = $this->createClassMetadataFactory()->getMetadataFor(ExplicitSchemaAndTable::class);

        self::assertSame('explicit_schema', $metadata->getSchemaName());
        self::assertSame('explicit_table', $metadata->getTableName());
    }

    /**
     * @group DDC-2825
     * @group 881
     */
    public function testSchemaDefinitionViaSchemaDefinedInTableNameInTableAnnotationProperty()
    {
        /* @var $metadata \Doctrine\ORM\Mapping\ClassMetadata */
        $metadata = $this->createClassMetadataFactory()->getMetadataFor(SchemaAndTableInTableName::class);

        self::assertSame('implicit_schema', $metadata->getSchemaName());
        self::assertSame('implicit_table', $metadata->getTableName());
    }

    /**
     * @group DDC-514
     * @group DDC-1015
     */
    public function testDiscriminatorColumnDefaultLength()
    {
        if (strpos(get_class($this), 'PHPMappingDriver') !== false) {
            $this->markTestSkipped('PHP Mapping Drivers have no defaults.');
        }
        $class = $this->createClassMetadata(SingleTableEntityNoDiscriminatorColumnMapping::class);
        self::assertEquals(255, $class->discriminatorColumn['length']);
        $class = $this->createClassMetadata(SingleTableEntityIncompleteDiscriminatorColumnMapping::class);
        self::assertEquals(255, $class->discriminatorColumn['length']);
    }

    /**
     * @group DDC-514
     * @group DDC-1015
     */
    public function testDiscriminatorColumnDefaultType()
    {
        if (strpos(get_class($this), 'PHPMappingDriver') !== false) {
            $this->markTestSkipped('PHP Mapping Drivers have no defaults.');
        }
        $class = $this->createClassMetadata(SingleTableEntityNoDiscriminatorColumnMapping::class);
        self::assertEquals('string', $class->discriminatorColumn['type']->getName());
        $class = $this->createClassMetadata(SingleTableEntityIncompleteDiscriminatorColumnMapping::class);
        self::assertEquals('string', $class->discriminatorColumn['type']->getName());
    }

    /**
     * @group DDC-514
     * @group DDC-1015
     */
    public function testDiscriminatorColumnDefaultName()
    {
        if (strpos(get_class($this), 'PHPMappingDriver') !== false) {
            $this->markTestSkipped('PHP Mapping Drivers have no defaults.');
        }
        $class = $this->createClassMetadata(SingleTableEntityNoDiscriminatorColumnMapping::class);
        self::assertEquals('dtype', $class->discriminatorColumn['name']);
        $class = $this->createClassMetadata(SingleTableEntityIncompleteDiscriminatorColumnMapping::class);
        self::assertEquals('dtype', $class->discriminatorColumn['name']);
    }

}

/**
 * @Entity
 * @HasLifecycleCallbacks
 * @Table(
 *  name="cms_users",
 *  uniqueConstraints={@UniqueConstraint(name="search_idx", columns={"name", "user_email"}, options={"where": "name IS NOT NULL"})},
 *  indexes={@Index(name="name_idx", columns={"name"}), @Index(name="0", columns={"user_email"})},
 *  options={"foo": "bar", "baz": {"key": "val"}}
 * )
 * @NamedQueries({@NamedQuery(name="all", query="SELECT u FROM __CLASS__ u")})
 */
class User
{
    /**
     * @Id
     * @Column(type="integer", options={"foo": "bar", "unsigned": false})
     * @generatedValue(strategy="AUTO")
     * @SequenceGenerator(sequenceName="tablename_seq", initialValue=1, allocationSize=100)
     **/
    public $id;

    /**
     * @Column(length=50, nullable=true, unique=true, options={"foo": "bar", "baz": {"key": "val"}, "fixed": false})
     */
    public $name;

    /**
     * @Column(name="user_email", columnDefinition="CHAR(32) NOT NULL")
     */
    public $email;

    /**
     * @OneToOne(targetEntity="Address", cascade={"remove"}, inversedBy="user")
     * @JoinColumn(onDelete="CASCADE")
     */
    public $address;

    /**
     * @OneToMany(targetEntity="Phonenumber", mappedBy="user", cascade={"persist"}, orphanRemoval=true)
     * @OrderBy({"number"="ASC"})
     */
    public $phonenumbers;

    /**
     * @ManyToMany(targetEntity="Group", cascade={"all"})
     * @JoinTable(name="cms_user_groups",
     *    joinColumns={@JoinColumn(name="user_id", referencedColumnName="id", nullable=false, unique=false)},
     *    inverseJoinColumns={@JoinColumn(name="group_id", referencedColumnName="id", columnDefinition="INT NULL")}
     * )
     */
    public $groups;

    /**
     * @Column(type="integer")
     * @Version
     */
    public $version;


    /**
     * @PrePersist
     */
    public function doStuffOnPrePersist()
    {
    }

    /**
     * @PrePersist
     */
    public function doOtherStuffOnPrePersistToo() {
    }

    /**
     * @PostPersist
     */
    public function doStuffOnPostPersist()
    {

    }

    public static function loadMetadata(ClassMetadata $metadata)
    {
        $metadata->setInheritanceType(ClassMetadata::INHERITANCE_TYPE_NONE);
        $metadata->setPrimaryTable(
            [
                'name'    => 'cms_users',
                'options' => [
                    'foo' => 'bar',
                    'baz' => ['key' => 'val']
                ],
            ]
        );

        $metadata->setChangeTrackingPolicy(ClassMetadata::CHANGETRACKING_DEFERRED_IMPLICIT);

        $metadata->addLifecycleCallback('doStuffOnPrePersist', 'prePersist');
        $metadata->addLifecycleCallback('doOtherStuffOnPrePersistToo', 'prePersist');
        $metadata->addLifecycleCallback('doStuffOnPostPersist', 'postPersist');

        $metadata->addProperty(
            'id',
            Type::getType('integer'),
            [
                'id'      => true,
                'options' => ['foo' => 'bar', 'unsigned' => false],
            ]
        );

        $metadata->addProperty(
            'name',
            Type::getType('string'),
            [
                'length'   => 50,
                'unique'   => true,
                'nullable' => true,
                'options'  => [
                    'foo' => 'bar',
                    'baz' => ['key' => 'val'],
                    'fixed' => false
                ],
            ]
        );

        $metadata->addProperty('email', Type::getType('string'), [
            'columnName'       => 'user_email',
            'columnDefinition' => 'CHAR(32) NOT NULL',
        ]
        );

        $mapping = ['fieldName' => 'version', 'type' => 'integer'];

        $metadata->setVersionMapping($mapping);
        $metadata->addProperty('version', Type::getType('integer'));

        $metadata->setIdGeneratorType(ClassMetadata::GENERATOR_TYPE_AUTO);
        $metadata->mapOneToOne(
            [
               'fieldName' => 'address',
               'targetEntity' => Address::class,
               'cascade' => [0 => 'remove'],
               'mappedBy' => NULL,
               'inversedBy' => 'user',
               'joinColumns' => [
                   0 => [
                    'name' => 'address_id',
                    'referencedColumnName' => 'id',
                    'onDelete' => 'CASCADE',
                   ],
               ],
               'orphanRemoval' => false,
           ]
        );
        $metadata->mapOneToMany(
            [
               'fieldName' => 'phonenumbers',
               'targetEntity' => Phonenumber::class,
               'cascade' => [1 => 'persist'],
               'mappedBy' => 'user',
               'orphanRemoval' => true,
               'orderBy' => ['number' => 'ASC'],
           ]
        );
        $metadata->mapManyToMany(
            [
                'fieldName' => 'groups',
                'targetEntity' => Group::class,
                'cascade' => [
                    0 => 'remove',
                    1 => 'persist',
                    2 => 'refresh',
                    3 => 'merge',
                    4 => 'detach',
                ],
                'mappedBy' => null,
                'joinTable' => [
                    'name' => 'cms_users_groups',
                    'joinColumns' => [
                        0 => [
                            'name' => 'user_id',
                            'referencedColumnName' => 'id',
                            'unique' => false,
                            'nullable' => false,
                        ],
                    ],
                    'inverseJoinColumns' => [
                        0 => [
                            'name' => 'group_id',
                            'referencedColumnName' => 'id',
                            'columnDefinition' => 'INT NULL',
                        ],
                    ],
                ],
                'orderBy' => null,
            ]
        );
        $metadata->table['uniqueConstraints'] = [
            'search_idx' => ['columns' => ['name', 'user_email'], 'options'=> ['where' => 'name IS NOT NULL']],
        ];
        $metadata->table['indexes'] = [
            'name_idx' => ['columns' => ['name']], 0 => ['columns' => ['user_email']]
        ];
        $metadata->setSequenceGeneratorDefinition(
            [
                'sequenceName' => 'tablename_seq',
                'allocationSize' => 100,
                'initialValue' => 1,
            ]
        );
        $metadata->addNamedQuery(
            [
                'name' => 'all',
                'query' => 'SELECT u FROM __CLASS__ u'
            ]
        );
    }
}

/**
 * @Entity
 * @InheritanceType("SINGLE_TABLE")
 * @DiscriminatorMap({"cat" = "Cat", "dog" = "Dog"})
 * @DiscriminatorColumn(name="discr", length=32, type="string")
 */
abstract class Animal
{
    /**
     * @Id @Column(type="string") @GeneratedValue(strategy="CUSTOM")
     * @CustomIdGenerator(class="stdClass")
     */
    public $id;

    public static function loadMetadata(ClassMetadata $metadata)
    {
        $metadata->setIdGeneratorType(ClassMetadata::GENERATOR_TYPE_CUSTOM);
        $metadata->setCustomGeneratorDefinition(["class" => "stdClass"]);
    }
}

/** @Entity */
class Cat extends Animal
{
    public static function loadMetadata(ClassMetadata $metadata)
    {

    }
}

/** @Entity */
class Dog extends Animal
{
    public static function loadMetadata(ClassMetadata $metadata)
    {

    }
}


/**
 * @Entity
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
     * @Id
     * @GeneratedValue(strategy="NONE")
     * @Column(type="integer", columnDefinition = "INT unsigned NOT NULL")
     **/
    private $id;

    /**
     * @Column(columnDefinition = "VARCHAR(255) NOT NULL")
     */
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

    public static function loadMetadata(ClassMetadata $metadata)
    {
        $metadata->addProperty(
            'id',
            Type::getType('integer'),
            [
                'id'               => true,
                'columnDefinition' => 'INT unsigned NOT NULL',
            ]
        );

        $metadata->addProperty(
            'value',
            Type::getType('string'),
            ['columnDefinition' => 'VARCHAR(255) NOT NULL']
        );

        $metadata->setIdGeneratorType(ClassMetadata::GENERATOR_TYPE_NONE);
    }

}

/**
 * @Entity
 * @InheritanceType("SINGLE_TABLE")
 * @DiscriminatorMap({"ONE" = "DDC807SubClasse1", "TWO" = "DDC807SubClasse2"})
 * @DiscriminatorColumn(name = "dtype", columnDefinition="ENUM('ONE','TWO')")
 */
class DDC807Entity
{
    /**
     * @Id
     * @Column(type="integer")
     * @GeneratedValue(strategy="NONE")
     **/
   public $id;

   public static function loadMetadata(ClassMetadata $metadata)
    {
         $metadata->addProperty(
             'id',
             Type::getType('string'),
             ['id' => true]
         );

        $metadata->setDiscriminatorColumn(
            [
                'name'              => "dtype",
                'type'              => "string",
                'columnDefinition'  => "ENUM('ONE','TWO')",
                'tableName'         => $metadata->getTableName(),
            ]
        );

        $metadata->setIdGeneratorType(ClassMetadata::GENERATOR_TYPE_NONE);
    }
}

class DDC807SubClasse1 {}
class DDC807SubClasse2 {}

class Address {}
class Phonenumber {}
class Group {}

/**
 * @Entity
 * @Table(indexes={@Index(columns={"content"}, flags={"fulltext"}, options={"where": "content IS NOT NULL"})})
 */
class Comment
{
    /**
     * @Column(type="text")
     */
    private $content;

    public static function loadMetadata(ClassMetadata $metadata)
    {
        $metadata->setInheritanceType(ClassMetadata::INHERITANCE_TYPE_NONE);

        $metadata->setPrimaryTable(
            [
                'indexes' => [
                    [
                        'columns' => ['content'],
                        'flags'   => ['fulltext'],
                        'options' => ['where' => 'content IS NOT NULL']
                    ],
                ]
            ]
        );

        $metadata->addProperty(
            'content',
            Type::getType('text'),
            [
                'length'   => null,
                'unique'   => false,
                'nullable' => false,
            ]
        );
    }
}

/**
 * @Entity
 * @InheritanceType("SINGLE_TABLE")
 * @DiscriminatorMap({
 *     "ONE" = "SingleTableEntityNoDiscriminatorColumnMappingSub1",
 *     "TWO" = "SingleTableEntityNoDiscriminatorColumnMappingSub2"
 * })
 */
class SingleTableEntityNoDiscriminatorColumnMapping
{
    /**
     * @Id
     * @Column(type="integer")
     * @GeneratedValue(strategy="NONE")
     */
    public $id;

    public static function loadMetadata(ClassMetadata $metadata)
    {
        $metadata->addProperty(
            'id',
            Type::getType('string'),
            ['id' => true]
        );

        $metadata->setIdGeneratorType(ClassMetadata::GENERATOR_TYPE_NONE);
    }
}

class SingleTableEntityNoDiscriminatorColumnMappingSub1 extends SingleTableEntityNoDiscriminatorColumnMapping {}
class SingleTableEntityNoDiscriminatorColumnMappingSub2 extends SingleTableEntityNoDiscriminatorColumnMapping {}

/**
 * @Entity
 * @InheritanceType("SINGLE_TABLE")
 * @DiscriminatorMap({
 *     "ONE" = "SingleTableEntityIncompleteDiscriminatorColumnMappingSub1",
 *     "TWO" = "SingleTableEntityIncompleteDiscriminatorColumnMappingSub2"
 * })
 * @DiscriminatorColumn(name="dtype")
 */
class SingleTableEntityIncompleteDiscriminatorColumnMapping
{
    /**
     * @Id
     * @Column(type="integer")
     * @GeneratedValue(strategy="NONE")
     */
    public $id;

    public static function loadMetadata(ClassMetadata $metadata)
    {
        // @todo: String != Integer and this should not work
        $metadata->addProperty(
            'id',
            Type::getType('string'),
            ['id' => true]
        );

        $metadata->setIdGeneratorType(ClassMetadata::GENERATOR_TYPE_NONE);
    }
}

class SingleTableEntityIncompleteDiscriminatorColumnMappingSub1
    extends SingleTableEntityIncompleteDiscriminatorColumnMapping {}

class SingleTableEntityIncompleteDiscriminatorColumnMappingSub2
    extends SingleTableEntityIncompleteDiscriminatorColumnMapping {}
