<?php

namespace Doctrine\Tests\ORM\Mapping;

use Doctrine\Common\Persistence\Mapping\RuntimeReflectionService;
use Doctrine\ORM\EntityManager;
use Doctrine\DBAL\Types\Type;
use Doctrine\ORM\Events;
use Doctrine\ORM\Mapping\ClassMetadata;
use Doctrine\ORM\Mapping\DiscriminatorColumnMetadata;
use Doctrine\ORM\Mapping\FieldMetadata;
use Doctrine\ORM\Mapping\ClassMetadataFactory;
use Doctrine\ORM\Mapping\JoinColumnMetadata;
use Doctrine\ORM\Mapping\MappingException;
use Doctrine\ORM\Mapping\UnderscoreNamingStrategy;
use Doctrine\Tests\Models\Cache\City;
use Doctrine\Tests\Models\DDC2825\ExplicitSchemaAndTable;
use Doctrine\Tests\Models\DDC2825\SchemaAndTableInTableName;
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

    public function testLoadMapping()
    {
        $entityClassName = 'Doctrine\Tests\ORM\Mapping\User';
        return $this->createClassMetadata($entityClassName);
    }

    /**
     * @depends testLoadMapping
     * @param ClassMetadata $class
     */
    public function testEntityTableNameAndInheritance($class)
    {
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
        self::assertEquals(array(
            'name_idx' => array('columns' => array('name')),
            0 => array('columns' => array('user_email'))
        ), $class->table['indexes']);

        return $class;
    }

    public function testEntityIndexFlagsAndPartialIndexes()
    {
        $class = $this->createClassMetadata('Doctrine\Tests\ORM\Mapping\Comment');

        self::assertEquals(array(
            0 => array(
                'columns' => array('content'),
                'flags' => array('fulltext'),
                'options' => array('where' => 'content IS NOT NULL'),
            )
        ), $class->table['indexes']);
    }

    /**
     * @depends testEntityTableNameAndInheritance
     * @param ClassMetadata $class
     */
    public function testEntityUniqueConstraints($class)
    {
        self::assertArrayHasKey('uniqueConstraints', $class->table,
            'ClassMetadata should have uniqueConstraints key in table property when Unique Constraints are set.');

        self::assertEquals(array(
            "search_idx" => array("columns" => array("name", "user_email"), 'options' => array('where' => 'name IS NOT NULL'))
        ), $class->table['uniqueConstraints']);

        return $class;
    }

    /**
     * @depends testEntityTableNameAndInheritance
     * @param ClassMetadata $class
     */
    public function testEntityOptions($class)
    {
        self::assertArrayHasKey('options', $class->table, 'ClassMetadata should have options key in table property.');

        self::assertEquals(array(
            'foo' => 'bar', 'baz' => array('key' => 'val')
        ), $class->table['options']);

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
            array(
                'sequenceName' => 'tablename_seq',
                'allocationSize' => 100,
                'initialValue' => 1,
            ),
            $class->sequenceGeneratorDefinition
        );
    }

    public function testEntityCustomGenerator()
    {
        $class = $this->createClassMetadata('Doctrine\Tests\ORM\Mapping\Animal');

        self::assertEquals(ClassMetadata::GENERATOR_TYPE_CUSTOM,
            $class->generatorType, "Generator Type");
        self::assertEquals(
            array("class" => "stdClass"),
            $class->customGeneratorDefinition,
            "Custom Generator Definition");
    }


    /**
     * @depends testEntityTableNameAndInheritance
     * @param ClassMetadata $class
     */
    public function testFieldMappings($class)
    {
        self::assertEquals(4, count($class->getProperties()));

        self::assertNotNull($class->getProperty('id'));
        self::assertNotNull($class->getProperty('name'));
        self::assertNotNull($class->getProperty('email'));
        self::assertNotNull($class->getProperty('version'));

        return $class;
    }

    /**
     * @depends testFieldMappings
     * @param ClassMetadata $class
     */
    public function testVersionProperty($class)
    {
        self::assertTrue($class->isVersioned());
        self::assertNotNull($class->versionProperty);

        $versionPropertyName = $class->versionProperty->getName();

        self::assertEquals("version", $versionPropertyName);
        self::assertNotNull($class->getProperty($versionPropertyName));
    }

    /**
     * @depends testEntityTableNameAndInheritance
     * @param ClassMetadata $class
     */
    public function testFieldMappingsColumnNames($class)
    {
        self::assertNotNull($class->getProperty('id'));
        self::assertNotNull($class->getProperty('name'));
        self::assertNotNull($class->getProperty('email'));

        self::assertEquals("id", $class->getProperty('id')->getColumnName());
        self::assertEquals("name", $class->getProperty('name')->getColumnName());
        self::assertEquals("user_email", $class->getProperty('email')->getColumnName());

        return $class;
    }

    /**
     * @depends testEntityTableNameAndInheritance
     * @param ClassMetadata $class
     */
    public function testStringFieldMappings($class)
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
     * @param ClassMetadata $class
     */
    public function testFieldOptions($class)
    {
        self::assertNotNull($class->getProperty('name'));

        $property = $class->getProperty('name');
        $expected = array('foo' => 'bar', 'baz' => array('key' => 'val'));

        self::assertEquals($expected, $property->getOptions());

        return $class;
    }

    /**
     * @depends testEntityTableNameAndInheritance
     * @param ClassMetadata $class
     */
    public function testIdFieldOptions($class)
    {
        self::assertNotNull($class->getProperty('id'));

        $property = $class->getProperty('id');
        $expected = array('foo' => 'bar');

        self::assertEquals($expected, $property->getOptions());

        return $class;
    }

    /**
     * @depends testFieldMappings
     * @param ClassMetadata $class
     */
    public function testIdentifier($class)
    {
        self::assertNotNull($class->getProperty('id'));

        $property = $class->getProperty('id');

        self::assertEquals('integer', $property->getTypeName());
        self::assertEquals(array('id'), $class->identifier);
        self::assertEquals(ClassMetadata::GENERATOR_TYPE_AUTO, $class->generatorType, "ID-Generator is not ClassMetadata::GENERATOR_TYPE_AUTO");

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
        self::assertEquals(['remove'], $class->associationMappings['address']['cascade']);

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
        self::assertTrue($class->associationMappings['phonenumbers']['orphanRemoval']);
        // Check cascading
        self::assertEquals(['persist', 'remove'], $class->associationMappings['phonenumbers']['cascade']);

        // Test Order By
        self::assertEquals(array('number' => 'ASC'), $class->associationMappings['phonenumbers']['orderBy']);

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
        self::assertEquals(['remove', 'persist', 'refresh', 'merge', 'detach'], $class->associationMappings['groups']['cascade']);

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
        $association = $class->associationMappings['groups'];
        $joinColumn  = reset($association['joinTable']['joinColumns']);

        self::assertFalse($joinColumn->isNullable());
        self::assertFalse($joinColumn->isUnique());

        return $class;
    }

    /**
     * @depends testJoinColumnUniqueAndNullable
     * @param ClassMetadata $class
     */
    public function testColumnDefinition($class)
    {
        self::assertNotNull($class->getProperty('email'));

        $property    = $class->getProperty('email');
        $association = $class->associationMappings['groups'];
        $joinColumn  = reset($association['joinTable']['inverseJoinColumns']);

        self::assertEquals("CHAR(32) NOT NULL", $property->getColumnDefinition());
        self::assertEquals("INT NULL", $joinColumn->getColumnDefinition());

        return $class;
    }

    /**
     * @depends testColumnDefinition
     * @param ClassMetadata $class
     */
    public function testJoinColumnOnDelete($class)
    {
        $association = $class->associationMappings['address'];
        $joinColumn  = reset($association['joinColumns']);

        self::assertEquals('CASCADE', $joinColumn->getOnDelete());

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

        $class = $this->createClassMetadata('Doctrine\Tests\ORM\Mapping\Animal');

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
    public function testMappedSuperclassWithRepository()
    {
        $em      = $this->_getTestEntityManager();
        $factory = $this->createClassMetadataFactory($em);
        $class   = $factory->getMetadataFor('Doctrine\Tests\Models\DDC869\DDC869CreditCardPayment');

        self::assertNotNull($class->getProperty('id'));
        self::assertNotNull($class->getProperty('value'));
        self::assertNotNull($class->getProperty('creditCardNumber'));
        self::assertEquals($class->customRepositoryClassName, "Doctrine\Tests\Models\DDC869\DDC869PaymentRepository");
        self::assertInstanceOf(
            "Doctrine\Tests\Models\DDC869\DDC869PaymentRepository",
            $em->getRepository("Doctrine\Tests\Models\DDC869\DDC869CreditCardPayment")
        );
        self::assertTrue($em->getRepository("Doctrine\Tests\Models\DDC869\DDC869ChequePayment")->isTrue());

        $class = $factory->getMetadataFor('Doctrine\Tests\Models\DDC869\DDC869ChequePayment');

        self::assertNotNull($class->getProperty('id'));
        self::assertNotNull($class->getProperty('value'));
        self::assertNotNull($class->getProperty('serialNumber'));
        self::assertEquals($class->customRepositoryClassName, "Doctrine\Tests\Models\DDC869\DDC869PaymentRepository");
        self::assertInstanceOf(
            "Doctrine\Tests\Models\DDC869\DDC869PaymentRepository",
            $em->getRepository("Doctrine\Tests\Models\DDC869\DDC869ChequePayment")
        );
        self::assertTrue($em->getRepository("Doctrine\Tests\Models\DDC869\DDC869ChequePayment")->isTrue());
    }

    /**
     * @group DDC-1476
     */
    public function testDefaultFieldType()
    {
        $factory = $this->createClassMetadataFactory();
        $class   = $factory->getMetadataFor('Doctrine\Tests\Models\DDC1476\DDC1476EntityWithDefaultFieldType');

        self::assertNotNull($class->getProperty('id'));
        self::assertNotNull($class->getProperty('name'));

        $idProperty = $class->getProperty('id');
        $nameProperty = $class->getProperty('name');

        self::assertInstanceOf(FieldMetadata::class, $idProperty);
        self::assertInstanceOf(FieldMetadata::class, $nameProperty);

        self::assertEquals('string', $idProperty->getTypeName());
        self::assertEquals('string', $nameProperty->getTypeName());

        self::assertEquals('id', $idProperty->getName());
        self::assertEquals('name', $nameProperty->getName());

        self::assertEquals('id', $idProperty->getColumnName());
        self::assertEquals('name', $nameProperty->getColumnName());

        self::assertEquals(ClassMetadata::GENERATOR_TYPE_NONE, $class->generatorType);
    }

    /**
     * @group DDC-1170
     */
    public function testIdentifierColumnDefinition()
    {
        $class = $this->createClassMetadata(__NAMESPACE__ . '\DDC1170Entity');

        self::assertNotNull($class->getProperty('id'));
        self::assertNotNull($class->getProperty('value'));

        self::assertEquals("INT unsigned NOT NULL", $class->getProperty('id')->getColumnDefinition());
        self::assertEquals("VARCHAR(255) NOT NULL", $class->getProperty('value')->getColumnDefinition());
    }

    /**
     * @group DDC-559
     */
    public function testNamingStrategy()
    {
        $em         = $this->_getTestEntityManager();
        $factory    = $this->createClassMetadataFactory($em);

        self::assertInstanceOf('Doctrine\ORM\Mapping\DefaultNamingStrategy', $em->getConfiguration()->getNamingStrategy());

        $em->getConfiguration()->setNamingStrategy(new UnderscoreNamingStrategy(CASE_UPPER));

        self::assertInstanceOf('Doctrine\ORM\Mapping\UnderscoreNamingStrategy', $em->getConfiguration()->getNamingStrategy());

        $class = $factory->getMetadataFor('Doctrine\Tests\Models\DDC1476\DDC1476EntityWithDefaultFieldType');

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
        $class = $this->createClassMetadata(__NAMESPACE__ . '\DDC807Entity');

        self::assertNotNull($class->discriminatorColumn);

        $discrColumn = $class->discriminatorColumn;

        self::assertEquals('dtype', $discrColumn->getColumnName());
        self::assertEquals("ENUM('ONE','TWO')", $discrColumn->getColumnDefinition());
    }

    /**
     * @group DDC-889
     */
    public function testInvalidEntityOrMappedSuperClassShouldMentionParentClasses()
    {
        $this->expectException(MappingException::class);
        $this->expectExceptionMessage('Class "Doctrine\Tests\Models\DDC889\DDC889Class" sub class of "Doctrine\Tests\Models\DDC889\DDC889SuperClass" is not a valid entity or mapped super class.');

        $this->createClassMetadata('Doctrine\Tests\Models\DDC889\DDC889Class');
    }

    /**
     * @group DDC-889
     */
    public function testIdentifierRequiredShouldMentionParentClasses()
    {
        $factory = $this->createClassMetadataFactory();

        $this->expectException(MappingException::class);
        $this->expectExceptionMessage('No identifier/primary key specified for Entity "Doctrine\Tests\Models\DDC889\DDC889Entity" sub class of "Doctrine\Tests\Models\DDC889\DDC889SuperClass". Every Entity must have an identifier/primary key.');

        $factory->getMetadataFor('Doctrine\Tests\Models\DDC889\DDC889Entity');
    }

    public function testNamedQuery()
    {
        $driver = $this->_loadDriver();
        $class = $this->createClassMetadata(__NAMESPACE__.'\User');

        self::assertCount(1, $class->getNamedQueries(), sprintf("Named queries not processed correctly by driver %s", get_class($driver)));
    }

    /**
     * @group DDC-1663
     */
    public function testNamedNativeQuery()
    {

        $class = $this->createClassMetadata('Doctrine\Tests\Models\CMS\CmsAddress');

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
        self::assertEquals('Doctrine\Tests\Models\CMS\CmsAddress',$findByIdQuery['resultClass']);
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
        self::assertEquals('Doctrine\Tests\Models\CMS\CmsAddress', $findAllMapping['entities'][0]['entityClass']);
        self::assertEquals(array('name'=>'id','column'=>'id'), $findAllMapping['entities'][0]['fields'][0]);
        self::assertEquals(array('name'=>'city','column'=>'city'), $findAllMapping['entities'][0]['fields'][1]);
        self::assertEquals(array('name'=>'country','column'=>'country'), $findAllMapping['entities'][0]['fields'][2]);

        $withoutFieldsMapping = $class->getSqlResultSetMapping('mapping-without-fields');
        self::assertEquals('mapping-without-fields', $withoutFieldsMapping['name']);
        self::assertEquals('Doctrine\Tests\Models\CMS\CmsAddress', $withoutFieldsMapping['entities'][0]['entityClass']);
        self::assertEquals(array(), $withoutFieldsMapping['entities'][0]['fields']);

        $countMapping = $class->getSqlResultSetMapping('mapping-count');
        self::assertEquals('mapping-count', $countMapping['name']);
        self::assertEquals(array('name'=>'count'), $countMapping['columns'][0]);

    }

    /**
     * @group DDC-1663
     */
    public function testSqlResultSetMapping()
    {

        $userMetadata   = $this->createClassMetadata('Doctrine\Tests\Models\CMS\CmsUser');
        $personMetadata = $this->createClassMetadata('Doctrine\Tests\Models\Company\CompanyPerson');

        // user asserts
        self::assertCount(4, $userMetadata->getSqlResultSetMappings());

        $mapping = $userMetadata->getSqlResultSetMapping('mappingJoinedAddress');
        self::assertEquals(array(),$mapping['columns']);
        self::assertEquals('mappingJoinedAddress', $mapping['name']);
        self::assertNull($mapping['entities'][0]['discriminatorColumn']);
        self::assertEquals(array('name'=>'id','column'=>'id'),                     $mapping['entities'][0]['fields'][0]);
        self::assertEquals(array('name'=>'name','column'=>'name'),                 $mapping['entities'][0]['fields'][1]);
        self::assertEquals(array('name'=>'status','column'=>'status'),             $mapping['entities'][0]['fields'][2]);
        self::assertEquals(array('name'=>'address.zip','column'=>'zip'),           $mapping['entities'][0]['fields'][3]);
        self::assertEquals(array('name'=>'address.city','column'=>'city'),         $mapping['entities'][0]['fields'][4]);
        self::assertEquals(array('name'=>'address.country','column'=>'country'),   $mapping['entities'][0]['fields'][5]);
        self::assertEquals(array('name'=>'address.id','column'=>'a_id'),           $mapping['entities'][0]['fields'][6]);
        self::assertEquals($userMetadata->name,                                    $mapping['entities'][0]['entityClass']);


        $mapping = $userMetadata->getSqlResultSetMapping('mappingJoinedPhonenumber');
        self::assertEquals(array(),$mapping['columns']);
        self::assertEquals('mappingJoinedPhonenumber', $mapping['name']);
        self::assertNull($mapping['entities'][0]['discriminatorColumn']);
        self::assertEquals(array('name'=>'id','column'=>'id'),                             $mapping['entities'][0]['fields'][0]);
        self::assertEquals(array('name'=>'name','column'=>'name'),                         $mapping['entities'][0]['fields'][1]);
        self::assertEquals(array('name'=>'status','column'=>'status'),                     $mapping['entities'][0]['fields'][2]);
        self::assertEquals(array('name'=>'phonenumbers.phonenumber','column'=>'number'),   $mapping['entities'][0]['fields'][3]);
        self::assertEquals($userMetadata->name,                                            $mapping['entities'][0]['entityClass']);

        $mapping = $userMetadata->getSqlResultSetMapping('mappingUserPhonenumberCount');
        self::assertEquals(array('name'=>'numphones'),$mapping['columns'][0]);
        self::assertEquals('mappingUserPhonenumberCount', $mapping['name']);
        self::assertNull($mapping['entities'][0]['discriminatorColumn']);
        self::assertEquals(array('name'=>'id','column'=>'id'),         $mapping['entities'][0]['fields'][0]);
        self::assertEquals(array('name'=>'name','column'=>'name'),     $mapping['entities'][0]['fields'][1]);
        self::assertEquals(array('name'=>'status','column'=>'status'), $mapping['entities'][0]['fields'][2]);
        self::assertEquals($userMetadata->name,                        $mapping['entities'][0]['entityClass']);

        $mapping = $userMetadata->getSqlResultSetMapping('mappingMultipleJoinsEntityResults');
        self::assertEquals(array('name'=>'numphones'),$mapping['columns'][0]);
        self::assertEquals('mappingMultipleJoinsEntityResults', $mapping['name']);
        self::assertNull($mapping['entities'][0]['discriminatorColumn']);
        self::assertEquals(array('name'=>'id','column'=>'u_id'),           $mapping['entities'][0]['fields'][0]);
        self::assertEquals(array('name'=>'name','column'=>'u_name'),       $mapping['entities'][0]['fields'][1]);
        self::assertEquals(array('name'=>'status','column'=>'u_status'),   $mapping['entities'][0]['fields'][2]);
        self::assertEquals($userMetadata->name,                            $mapping['entities'][0]['entityClass']);
        self::assertNull($mapping['entities'][1]['discriminatorColumn']);
        self::assertEquals(array('name'=>'id','column'=>'a_id'),           $mapping['entities'][1]['fields'][0]);
        self::assertEquals(array('name'=>'zip','column'=>'a_zip'),         $mapping['entities'][1]['fields'][1]);
        self::assertEquals(array('name'=>'country','column'=>'a_country'), $mapping['entities'][1]['fields'][2]);
        self::assertEquals('Doctrine\Tests\Models\CMS\CmsAddress',         $mapping['entities'][1]['entityClass']);

        //person asserts
        self::assertCount(1, $personMetadata->getSqlResultSetMappings());

        $mapping = $personMetadata->getSqlResultSetMapping('mappingFetchAll');

        self::assertEquals(array(),$mapping['columns']);
        self::assertEquals('mappingFetchAll', $mapping['name']);
        self::assertEquals('discriminator',                            $mapping['entities'][0]['discriminatorColumn']);
        self::assertEquals(array('name'=>'id','column'=>'id'),         $mapping['entities'][0]['fields'][0]);
        self::assertEquals(array('name'=>'name','column'=>'name'),     $mapping['entities'][0]['fields'][1]);
        self::assertEquals($personMetadata->name,                      $mapping['entities'][0]['entityClass']);
    }

    /*
     * @group DDC-964
     */
    public function testAssociationOverridesMapping()
    {

        $factory        = $this->createClassMetadataFactory();
        $adminMetadata  = $factory->getMetadataFor('Doctrine\Tests\Models\DDC964\DDC964Admin');
        $guestMetadata  = $factory->getMetadataFor('Doctrine\Tests\Models\DDC964\DDC964Guest');

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
        self::assertEquals($guestGroups['cascade'], $adminGroups['cascade']);

         // assert not override attributes
        $guestGroupsJoinColumn        = reset($guestGroups['joinTable']['joinColumns']);
        $guestGroupsInverseJoinColumn = reset($guestGroups['joinTable']['inverseJoinColumns']);

        self::assertEquals('ddc964_users_groups', $guestGroups['joinTable']['name']);
        self::assertEquals('user_id', $guestGroupsJoinColumn->getColumnName());
        self::assertEquals('group_id', $guestGroupsInverseJoinColumn->getColumnName());

        self::assertEquals(array('user_id'=>'id'), $guestGroups['relationToSourceKeyColumns']);
        self::assertEquals(array('group_id'=>'id'), $guestGroups['relationToTargetKeyColumns']);

        $adminGroupsJoinColumn        = reset($adminGroups['joinTable']['joinColumns']);
        $adminGroupsInverseJoinColumn = reset($adminGroups['joinTable']['inverseJoinColumns']);

        self::assertEquals('ddc964_users_admingroups', $adminGroups['joinTable']['name']);
        self::assertEquals('adminuser_id', $guestGroupsJoinColumn->getColumnName());
        self::assertEquals('admingroup_id', $guestGroupsInverseJoinColumn->getColumnName());

        self::assertEquals(array('adminuser_id'=>'id'), $adminGroups['relationToSourceKeyColumns']);
        self::assertEquals(array('admingroup_id'=>'id'), $adminGroups['relationToTargetKeyColumns']);

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
        self::assertEquals($guestAddress['cascade'], $adminAddress['cascade']);

        // assert override
        $guestAddressJoinColumn = reset($guestAddress['joinColumns']);

        self::assertEquals('address_id', $guestAddressJoinColumn->getColumnName());
        self::assertEquals(array('address_id'=>'id'), $guestAddress['sourceToTargetKeyColumns']);
        self::assertEquals(array('address_id'=>'address_id'), $guestAddress['joinColumnFieldNames']);
        self::assertEquals(array('id'=>'address_id'), $guestAddress['targetToSourceKeyColumns']);

        $adminAddressJoinColumn = reset($adminAddress['joinColumns']);

        self::assertEquals('adminaddress_id', $adminAddressJoinColumn->getColumnName());
        self::assertEquals(array('adminaddress_id'=>'id'), $adminAddress['sourceToTargetKeyColumns']);
        self::assertEquals(array('adminaddress_id'=>'adminaddress_id'), $adminAddress['joinColumnFieldNames']);
        self::assertEquals(array('id'=>'adminaddress_id'), $adminAddress['targetToSourceKeyColumns']);
    }

    /*
     * @group DDC-3579
     */
    public function testInversedByOverrideMapping()
    {
        $factory        = $this->createClassMetadataFactory();
        $adminMetadata  = $factory->getMetadataFor('Doctrine\Tests\Models\DDC3579\DDC3579Admin');

        // assert groups association mappings
        self::assertArrayHasKey('groups', $adminMetadata->associationMappings);
        $adminGroups = $adminMetadata->associationMappings['groups'];

        // assert override
        self::assertEquals('admins', $adminGroups['inversedBy']);
    }

    /**
     * @group DDC-964
     */
    public function testAttributeOverridesMapping()
    {
        $factory       = $this->createClassMetadataFactory();
        $adminMetadata = $factory->getMetadataFor('Doctrine\Tests\Models\DDC964\DDC964Admin');

        self::assertEquals(array('user_id'=>'id', 'user_name'=>'name'), $adminMetadata->fieldNames);

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

        $guestMetadata = $factory->getMetadataFor('Doctrine\Tests\Models\DDC964\DDC964Guest');

        self::assertEquals(array('guest_id'=>'id','guest_name'=>'name'), $guestMetadata->fieldNames);

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
    public function testEntityListeners()
    {
        $em         = $this->_getTestEntityManager();
        $factory    = $this->createClassMetadataFactory($em);
        $superClass = $factory->getMetadataFor('Doctrine\Tests\Models\Company\CompanyContract');
        $flexClass  = $factory->getMetadataFor('Doctrine\Tests\Models\Company\CompanyFixContract');
        $fixClass   = $factory->getMetadataFor('Doctrine\Tests\Models\Company\CompanyFlexContract');
        $ultraClass = $factory->getMetadataFor('Doctrine\Tests\Models\Company\CompanyFlexUltraContract');

        self::assertArrayHasKey(Events::prePersist, $superClass->entityListeners);
        self::assertArrayHasKey(Events::postPersist, $superClass->entityListeners);

        self::assertCount(1, $superClass->entityListeners[Events::prePersist]);
        self::assertCount(1, $superClass->entityListeners[Events::postPersist]);

        $postPersist = $superClass->entityListeners[Events::postPersist][0];
        $prePersist  = $superClass->entityListeners[Events::prePersist][0];

        self::assertEquals('Doctrine\Tests\Models\Company\CompanyContractListener', $postPersist['class']);
        self::assertEquals('Doctrine\Tests\Models\Company\CompanyContractListener', $prePersist['class']);
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
        $ultraClass = $factory->getMetadataFor('Doctrine\Tests\Models\Company\CompanyFlexUltraContract');

        //overridden listeners
        self::assertArrayHasKey(Events::postPersist, $ultraClass->entityListeners);
        self::assertArrayHasKey(Events::prePersist, $ultraClass->entityListeners);

        self::assertCount(1, $ultraClass->entityListeners[Events::postPersist]);
        self::assertCount(3, $ultraClass->entityListeners[Events::prePersist]);

        $postPersist = $ultraClass->entityListeners[Events::postPersist][0];
        $prePersist  = $ultraClass->entityListeners[Events::prePersist][0];

        self::assertEquals('Doctrine\Tests\Models\Company\CompanyContractListener', $postPersist['class']);
        self::assertEquals('Doctrine\Tests\Models\Company\CompanyContractListener', $prePersist['class']);
        self::assertEquals('postPersistHandler', $postPersist['method']);
        self::assertEquals('prePersistHandler', $prePersist['method']);

        $prePersist = $ultraClass->entityListeners[Events::prePersist][1];
        self::assertEquals('Doctrine\Tests\Models\Company\CompanyFlexUltraContractListener', $prePersist['class']);
        self::assertEquals('prePersistHandler1', $prePersist['method']);

        $prePersist = $ultraClass->entityListeners[Events::prePersist][2];
        self::assertEquals('Doctrine\Tests\Models\Company\CompanyFlexUltraContractListener', $prePersist['class']);
        self::assertEquals('prePersistHandler2', $prePersist['method']);
    }


    /**
     * @group DDC-1955
     */
    public function testEntityListenersNamingConvention()
    {
        $em         = $this->_getTestEntityManager();
        $factory    = $this->createClassMetadataFactory($em);
        $metadata   = $factory->getMetadataFor('Doctrine\Tests\Models\CMS\CmsAddress');

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


        self::assertEquals('Doctrine\Tests\Models\CMS\CmsAddressListener', $postPersist['class']);
        self::assertEquals('Doctrine\Tests\Models\CMS\CmsAddressListener', $prePersist['class']);
        self::assertEquals('Doctrine\Tests\Models\CMS\CmsAddressListener', $postUpdate['class']);
        self::assertEquals('Doctrine\Tests\Models\CMS\CmsAddressListener', $preUpdate['class']);
        self::assertEquals('Doctrine\Tests\Models\CMS\CmsAddressListener', $postRemove['class']);
        self::assertEquals('Doctrine\Tests\Models\CMS\CmsAddressListener', $preRemove['class']);
        self::assertEquals('Doctrine\Tests\Models\CMS\CmsAddressListener', $postLoad['class']);
        self::assertEquals('Doctrine\Tests\Models\CMS\CmsAddressListener', $preFlush['class']);

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
        $class   = $factory->getMetadataFor(City::CLASSNAME);

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
        $metadata = $this->createClassMetadataFactory()->getMetadataFor(ExplicitSchemaAndTable::CLASSNAME);

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
        $metadata = $this->createClassMetadataFactory()->getMetadataFor(SchemaAndTableInTableName::CLASSNAME);

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

        $class = $this->createClassMetadata(__NAMESPACE__ . '\SingleTableEntityNoDiscriminatorColumnMapping');

        self::assertEquals(255, $class->discriminatorColumn->getLength());

        $class = $this->createClassMetadata(__NAMESPACE__ . '\SingleTableEntityIncompleteDiscriminatorColumnMapping');

        self::assertEquals(255, $class->discriminatorColumn->getLength());
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

        $class = $this->createClassMetadata(__NAMESPACE__ . '\SingleTableEntityNoDiscriminatorColumnMapping');

        self::assertEquals('string', $class->discriminatorColumn->getTypeName());

        $class = $this->createClassMetadata(__NAMESPACE__ . '\SingleTableEntityIncompleteDiscriminatorColumnMapping');

        self::assertEquals('string', $class->discriminatorColumn->getTypeName());
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

        $class = $this->createClassMetadata(__NAMESPACE__ . '\SingleTableEntityNoDiscriminatorColumnMapping');

        self::assertEquals('dtype', $class->discriminatorColumn->getColumnName());

        $class = $this->createClassMetadata(__NAMESPACE__ . '\SingleTableEntityIncompleteDiscriminatorColumnMapping');

        self::assertEquals('dtype', $class->discriminatorColumn->getColumnName());
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
     * @Column(type="integer", options={"foo": "bar"})
     * @generatedValue(strategy="AUTO")
     * @SequenceGenerator(sequenceName="tablename_seq", initialValue=1, allocationSize=100)
     **/
    public $id;

    /**
     * @Column(length=50, nullable=true, unique=true, options={"foo": "bar", "baz": {"key": "val"}})
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
        $metadata->setPrimaryTable(array(
            'name'    => 'cms_users',
            'options' => array(
                'foo' => 'bar',
                'baz' => array('key' => 'val')
            ),
        ));

        $metadata->setChangeTrackingPolicy(ClassMetadata::CHANGETRACKING_DEFERRED_IMPLICIT);

        $metadata->addLifecycleCallback('doStuffOnPrePersist', 'prePersist');
        $metadata->addLifecycleCallback('doOtherStuffOnPrePersistToo', 'prePersist');
        $metadata->addLifecycleCallback('doStuffOnPostPersist', 'postPersist');

        $metadata->addProperty('id', Type::getType('integer'), array(
            'id'      => true,
            'options' => array('foo' => 'bar'),
        ));

        $metadata->addProperty('name', Type::getType('string'), array(
            'length'   => 50,
            'unique'   => true,
            'nullable' => true,
            'options'  => array(
                'foo' => 'bar',
                'baz' => array('key' => 'val')
            ),
        ));

        $metadata->addProperty('email', Type::getType('string'), array(
            'columnName'       => 'user_email',
            'columnDefinition' => 'CHAR(32) NOT NULL',
        ));

        $property = $metadata->addProperty('version', Type::getType('integer'));

        $metadata->setVersionProperty($property);
        
        $metadata->setIdGeneratorType(ClassMetadata::GENERATOR_TYPE_AUTO);

        $joinColumns = array();

        $joinColumn = new JoinColumnMetadata();

        $joinColumn->setColumnName('address_id');
        $joinColumn->setReferencedColumnName('id');
        $joinColumn->setOnDelete('CASCADE');

        $joinColumns[] = $joinColumn;

        $metadata->mapOneToOne(array(
            'fieldName'     => 'address',
            'targetEntity'  => 'Doctrine\\Tests\\ORM\\Mapping\\Address',
            'cascade'       => array('remove'),
            'inversedBy'    => 'user',
            'joinColumns'   => $joinColumns,
            'orphanRemoval' => false,
        ));

        $metadata->mapOneToMany(array(
            'fieldName'     => 'phonenumbers',
            'targetEntity'  => 'Doctrine\\Tests\\ORM\\Mapping\\Phonenumber',
            'cascade'       => array('persist'),
            'mappedBy'      => 'user',
            'orphanRemoval' => true,
            'orderBy'       => array(
                'number' => 'ASC',
            ),
        ));

        $joinColumns = $inverseJoinColumns = array();

        $joinColumn = new JoinColumnMetadata();

        $joinColumn->setColumnName('user_id');
        $joinColumn->setReferencedColumnName('id');

        $joinColumns[] = $joinColumn;

        $joinColumn = new JoinColumnMetadata();

        $joinColumn->setColumnName('group_id');
        $joinColumn->setReferencedColumnName('id');
        $joinColumn->setColumnDefinition('INT NULL');

        $inverseJoinColumns[] = $joinColumn;

        $joinTable = array(
            'name'               => 'cms_users_groups',
            'joinColumns'        => $joinColumns,
            'inverseJoinColumns' => $inverseJoinColumns,
        );

        $metadata->mapManyToMany(array(
            'fieldName'    => 'groups',
            'targetEntity' => 'Doctrine\\Tests\\ORM\\Mapping\\Group',
            'cascade'      => array('remove', 'persist', 'refresh', 'merge', 'detach'),
            'joinTable'    => $joinTable,
            'orderBy' => NULL,
        ));

        $metadata->table['uniqueConstraints'] = array(
            'search_idx' => array(
                'columns' => array('name', 'user_email'),
                'options'=> array('where' => 'name IS NOT NULL')
            ),
        );

        $metadata->table['indexes'] = array(
            'name_idx' => array(
                'columns' => array('name')
            ),
            0 => array( // Unnamed index
                'columns' => array('user_email')
            ),
        );

        $metadata->setSequenceGeneratorDefinition(array(
            'sequenceName' => 'tablename_seq',
            'allocationSize' => 100,
            'initialValue' => 1,
        ));

        $metadata->addNamedQuery(array(
            'name' => 'all',
            'query' => 'SELECT u FROM __CLASS__ u'
        ));
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
        $metadata->setCustomGeneratorDefinition(array("class" => "stdClass"));
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
        $metadata->addProperty('id', Type::getType('integer'), array(
           'id'               => true,
           'columnDefinition' => 'INT unsigned NOT NULL',
        ));

        $metadata->addProperty('value', Type::getType('string'), array(
            'columnDefinition' => 'VARCHAR(255) NOT NULL',
        ));

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
        $metadata->addProperty('id', Type::getType('string'), array(
           'id' => true,
        ));

        $discrColumn = new DiscriminatorColumnMetadata();

        $discrColumn->setTableName($metadata->getTableName());
        $discrColumn->setColumnName('dtype');
        $discrColumn->setType(Type::getType('string'));
        $discrColumn->setColumnDefinition("ENUM('ONE','TWO')");

        $metadata->setDiscriminatorColumn($discrColumn);

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

        $metadata->setPrimaryTable(array(
            'indexes' => array(
                array(
                    'columns' => array('content'),
                    'flags'   => array('fulltext'),
                    'options' => array('where' => 'content IS NOT NULL')
                ),
            )
        ));

        $metadata->addProperty('content', Type::getType('text'), array(
            'length'   => NULL,
            'unique'   => false,
            'nullable' => false,
        ));
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
        $metadata->addProperty('id', Type::getType('string'), array(
            'id' => true,
        ));

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
        $metadata->addProperty('id', Type::getType('string'), array(
            'id' => true,
        ));

        $metadata->setIdGeneratorType(ClassMetadata::GENERATOR_TYPE_NONE);
    }
}

class SingleTableEntityIncompleteDiscriminatorColumnMappingSub1
    extends SingleTableEntityIncompleteDiscriminatorColumnMapping {}

class SingleTableEntityIncompleteDiscriminatorColumnMappingSub2
    extends SingleTableEntityIncompleteDiscriminatorColumnMapping {}