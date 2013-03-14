<?php

namespace Doctrine\Tests\ORM\Mapping;

use Doctrine\ORM\Mapping\ClassMetadata,
    Doctrine\ORM\Mapping\ClassMetadataInfo,
    Doctrine\ORM\Mapping\Driver\XmlDriver,
    Doctrine\ORM\Mapping\Driver\YamlDriver;

require_once __DIR__ . '/../../TestInit.php';

abstract class AbstractMappingDriverTest extends \Doctrine\Tests\OrmTestCase
{
    abstract protected function _loadDriver();

    public function createClassMetadata($entityClassName)
    {
        $mappingDriver = $this->_loadDriver();

        $class = new ClassMetadata($entityClassName);
        $class->initializeReflection(new \Doctrine\Common\Persistence\Mapping\RuntimeReflectionService);
        $mappingDriver->loadMetadataForClass($entityClassName, $class);

        return $class;
    }

    /**
     * @param \Doctrine\ORM\EntityManager $entityClassName
     * @return \Doctrine\ORM\Mapping\ClassMetadataFactory
     */
    protected function createClassMetadataFactory(\Doctrine\ORM\EntityManager $em = null)
    {
        $driver     = $this->_loadDriver();
        $em         = $em ?: $this->_getTestEntityManager();
        $factory    = new \Doctrine\ORM\Mapping\ClassMetadataFactory();
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
        $this->assertEquals('cms_users', $class->getTableName());
        $this->assertEquals(ClassMetadata::INHERITANCE_TYPE_NONE, $class->inheritanceType);

        return $class;
    }

    /**
     * @depends testEntityTableNameAndInheritance
     * @param ClassMetadata $class
     */
    public function testEntityIndexes($class)
    {
        $this->assertArrayHasKey('indexes', $class->table, 'ClassMetadata should have indexes key in table property.');
        $this->assertEquals(array(
            'name_idx' => array('columns' => array('name')),
            0 => array('columns' => array('user_email'))
        ), $class->table['indexes']);

        return $class;
    }

    /**
     * @depends testEntityTableNameAndInheritance
     * @param ClassMetadata $class
     */
    public function testEntityUniqueConstraints($class)
    {
        $this->assertArrayHasKey('uniqueConstraints', $class->table,
            'ClassMetadata should have uniqueConstraints key in table property when Unique Constraints are set.');

        $this->assertEquals(array(
            "search_idx" => array("columns" => array("name", "user_email"))
        ), $class->table['uniqueConstraints']);

        return $class;
    }

    /**
     * @depends testEntityTableNameAndInheritance
     * @param ClassMetadata $class
     */
    public function testEntityOptions($class)
    {
        $this->assertArrayHasKey('options', $class->table, 'ClassMetadata should have options key in table property.');

        $this->assertEquals(array(
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
        $this->assertInternalType('array', $class->sequenceGeneratorDefinition, 'No Sequence Definition set on this driver.');
        $this->assertEquals(
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

        $this->assertEquals(ClassMetadata::GENERATOR_TYPE_CUSTOM,
            $class->generatorType, "Generator Type");
        $this->assertEquals(
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
        $this->assertEquals(4, count($class->fieldMappings));
        $this->assertTrue(isset($class->fieldMappings['id']));
        $this->assertTrue(isset($class->fieldMappings['name']));
        $this->assertTrue(isset($class->fieldMappings['email']));
        $this->assertTrue(isset($class->fieldMappings['version']));

        return $class;
    }

    /**
     * @depends testFieldMappings
     * @param ClassMetadata $class
     */
    public function testVersionedField($class)
    {
        $this->assertTrue($class->isVersioned);
        $this->assertEquals("version", $class->versionField);

        $this->assertFalse(isset($class->fieldMappings['version']['version']));
    }

    /**
     * @depends testEntityTableNameAndInheritance
     * @param ClassMetadata $class
     */
    public function testFieldMappingsColumnNames($class)
    {
        $this->assertEquals("id", $class->fieldMappings['id']['columnName']);
        $this->assertEquals("name", $class->fieldMappings['name']['columnName']);
        $this->assertEquals("user_email", $class->fieldMappings['email']['columnName']);

        return $class;
    }

    /**
     * @depends testEntityTableNameAndInheritance
     * @param ClassMetadata $class
     */
    public function testStringFieldMappings($class)
    {
        $this->assertEquals('string', $class->fieldMappings['name']['type']);
        $this->assertEquals(50, $class->fieldMappings['name']['length']);
        $this->assertTrue($class->fieldMappings['name']['nullable']);
        $this->assertTrue($class->fieldMappings['name']['unique']);

        $expected = array('foo' => 'bar', 'baz' => array('key' => 'val'));
        $this->assertEquals($expected, $class->fieldMappings['name']['options']);

        return $class;
    }

    /**
     * @depends testFieldMappings
     * @param ClassMetadata $class
     */
    public function testIdentifier($class)
    {
        $this->assertEquals(array('id'), $class->identifier);
        $this->assertEquals('integer', $class->fieldMappings['id']['type']);
        $this->assertEquals(ClassMetadata::GENERATOR_TYPE_AUTO, $class->generatorType, "ID-Generator is not ClassMetadata::GENERATOR_TYPE_AUTO");

        return $class;
    }

    /**
     * @depends testIdentifier
     * @param ClassMetadata $class
     */
    public function testAssocations($class)
    {
        $this->assertEquals(3, count($class->associationMappings));

        return $class;
    }

    /**
     * @depends testAssocations
     * @param ClassMetadata $class
     */
    public function testOwningOneToOneAssocation($class)
    {
        $this->assertTrue(isset($class->associationMappings['address']));
        $this->assertTrue($class->associationMappings['address']['isOwningSide']);
        $this->assertEquals('user', $class->associationMappings['address']['inversedBy']);
        // Check cascading
        $this->assertTrue($class->associationMappings['address']['isCascadeRemove']);
        $this->assertFalse($class->associationMappings['address']['isCascadePersist']);
        $this->assertFalse($class->associationMappings['address']['isCascadeRefresh']);
        $this->assertFalse($class->associationMappings['address']['isCascadeDetach']);
        $this->assertFalse($class->associationMappings['address']['isCascadeMerge']);

        return $class;
    }

    /**
     * @depends testOwningOneToOneAssocation
     * @param ClassMetadata $class
     */
    public function testInverseOneToManyAssociation($class)
    {
        $this->assertTrue(isset($class->associationMappings['phonenumbers']));
        $this->assertFalse($class->associationMappings['phonenumbers']['isOwningSide']);
        $this->assertTrue($class->associationMappings['phonenumbers']['isCascadePersist']);
        $this->assertTrue($class->associationMappings['phonenumbers']['isCascadeRemove']);
        $this->assertFalse($class->associationMappings['phonenumbers']['isCascadeRefresh']);
        $this->assertFalse($class->associationMappings['phonenumbers']['isCascadeDetach']);
        $this->assertFalse($class->associationMappings['phonenumbers']['isCascadeMerge']);
        $this->assertTrue($class->associationMappings['phonenumbers']['orphanRemoval']);

        // Test Order By
        $this->assertEquals(array('number' => 'ASC'), $class->associationMappings['phonenumbers']['orderBy']);

        return $class;
    }

    /**
     * @depends testInverseOneToManyAssociation
     * @param ClassMetadata $class
     */
    public function testManyToManyAssociationWithCascadeAll($class)
    {
        $this->assertTrue(isset($class->associationMappings['groups']));
        $this->assertTrue($class->associationMappings['groups']['isOwningSide']);
        // Make sure that cascade-all works as expected
        $this->assertTrue($class->associationMappings['groups']['isCascadeRemove']);
        $this->assertTrue($class->associationMappings['groups']['isCascadePersist']);
        $this->assertTrue($class->associationMappings['groups']['isCascadeRefresh']);
        $this->assertTrue($class->associationMappings['groups']['isCascadeDetach']);
        $this->assertTrue($class->associationMappings['groups']['isCascadeMerge']);

        $this->assertFalse(isset($class->associationMappings['groups']['orderBy']));

        return $class;
    }

    /**
     * @depends testManyToManyAssociationWithCascadeAll
     * @param ClassMetadata $class
     */
    public function testLifecycleCallbacks($class)
    {
        $this->assertEquals(count($class->lifecycleCallbacks), 2);
        $this->assertEquals($class->lifecycleCallbacks['prePersist'][0], 'doStuffOnPrePersist');
        $this->assertEquals($class->lifecycleCallbacks['postPersist'][0], 'doStuffOnPostPersist');

        return $class;
    }

    /**
     * @depends testManyToManyAssociationWithCascadeAll
     * @param ClassMetadata $class
     */
    public function testLifecycleCallbacksSupportMultipleMethodNames($class)
    {
        $this->assertEquals(count($class->lifecycleCallbacks['prePersist']), 2);
        $this->assertEquals($class->lifecycleCallbacks['prePersist'][1], 'doOtherStuffOnPrePersistToo');

        return $class;
    }

    /**
     * @depends testLifecycleCallbacksSupportMultipleMethodNames
     * @param ClassMetadata $class
     */
    public function testJoinColumnUniqueAndNullable($class)
    {
        // Non-Nullability of Join Column
        $this->assertFalse($class->associationMappings['groups']['joinTable']['joinColumns'][0]['nullable']);
        $this->assertFalse($class->associationMappings['groups']['joinTable']['joinColumns'][0]['unique']);

        return $class;
    }

    /**
     * @depends testJoinColumnUniqueAndNullable
     * @param ClassMetadata $class
     */
    public function testColumnDefinition($class)
    {
        $this->assertEquals("CHAR(32) NOT NULL", $class->fieldMappings['email']['columnDefinition']);
        $this->assertEquals("INT NULL", $class->associationMappings['groups']['joinTable']['inverseJoinColumns'][0]['columnDefinition']);

        return $class;
    }

    /**
     * @depends testColumnDefinition
     * @param ClassMetadata $class
     */
    public function testJoinColumnOnDelete($class)
    {
        $this->assertEquals('CASCADE', $class->associationMappings['address']['joinColumns'][0]['onDelete']);

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

        $this->assertEquals(
            array('name' => 'discr', 'type' => 'string', 'length' => '32', 'fieldName' => 'discr', 'columnDefinition' => null),
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


        $class = $factory->getMetadataFor('Doctrine\Tests\Models\DDC869\DDC869CreditCardPayment');

        $this->assertTrue(isset($class->fieldMappings['id']));
        $this->assertTrue(isset($class->fieldMappings['value']));
        $this->assertTrue(isset($class->fieldMappings['creditCardNumber']));
        $this->assertEquals($class->customRepositoryClassName, "Doctrine\Tests\Models\DDC869\DDC869PaymentRepository");
        $this->assertInstanceOf("Doctrine\Tests\Models\DDC869\DDC869PaymentRepository",
             $em->getRepository("Doctrine\Tests\Models\DDC869\DDC869CreditCardPayment"));
        $this->assertTrue($em->getRepository("Doctrine\Tests\Models\DDC869\DDC869ChequePayment")->isTrue());



        $class = $factory->getMetadataFor('Doctrine\Tests\Models\DDC869\DDC869ChequePayment');

        $this->assertTrue(isset($class->fieldMappings['id']));
        $this->assertTrue(isset($class->fieldMappings['value']));
        $this->assertTrue(isset($class->fieldMappings['serialNumber']));
        $this->assertEquals($class->customRepositoryClassName, "Doctrine\Tests\Models\DDC869\DDC869PaymentRepository");
        $this->assertInstanceOf("Doctrine\Tests\Models\DDC869\DDC869PaymentRepository",
             $em->getRepository("Doctrine\Tests\Models\DDC869\DDC869ChequePayment"));
        $this->assertTrue($em->getRepository("Doctrine\Tests\Models\DDC869\DDC869ChequePayment")->isTrue());
    }

    /**
     * @group DDC-1476
     */
    public function testDefaultFieldType()
    {
        $factory    = $this->createClassMetadataFactory();
        $class      = $factory->getMetadataFor('Doctrine\Tests\Models\DDC1476\DDC1476EntityWithDefaultFieldType');


        $this->assertArrayHasKey('id', $class->fieldMappings);
        $this->assertArrayHasKey('name', $class->fieldMappings);


        $this->assertArrayHasKey('type', $class->fieldMappings['id']);
        $this->assertArrayHasKey('type', $class->fieldMappings['name']);

        $this->assertEquals('string', $class->fieldMappings['id']['type']);
        $this->assertEquals('string', $class->fieldMappings['name']['type']);



        $this->assertArrayHasKey('fieldName', $class->fieldMappings['id']);
        $this->assertArrayHasKey('fieldName', $class->fieldMappings['name']);

        $this->assertEquals('id', $class->fieldMappings['id']['fieldName']);
        $this->assertEquals('name', $class->fieldMappings['name']['fieldName']);



        $this->assertArrayHasKey('columnName', $class->fieldMappings['id']);
        $this->assertArrayHasKey('columnName', $class->fieldMappings['name']);

        $this->assertEquals('id', $class->fieldMappings['id']['columnName']);
        $this->assertEquals('name', $class->fieldMappings['name']['columnName']);

        $this->assertEquals(ClassMetadataInfo::GENERATOR_TYPE_NONE, $class->generatorType);
    }

    /**
     * @group DDC-1170
     */
    public function testIdentifierColumnDefinition()
    {
        $class = $this->createClassMetadata(__NAMESPACE__ . '\DDC1170Entity');


        $this->assertArrayHasKey('id', $class->fieldMappings);
        $this->assertArrayHasKey('value', $class->fieldMappings);

        $this->assertArrayHasKey('columnDefinition', $class->fieldMappings['id']);
        $this->assertArrayHasKey('columnDefinition', $class->fieldMappings['value']);

        $this->assertEquals("INT unsigned NOT NULL", $class->fieldMappings['id']['columnDefinition']);
        $this->assertEquals("VARCHAR(255) NOT NULL", $class->fieldMappings['value']['columnDefinition']);
    }

    /**
     * @group DDC-559
     */
    public function testNamingStrategy()
    {
        $em         = $this->_getTestEntityManager();
        $factory    = $this->createClassMetadataFactory($em);


        $this->assertInstanceOf('Doctrine\ORM\Mapping\DefaultNamingStrategy', $em->getConfiguration()->getNamingStrategy());
        $em->getConfiguration()->setNamingStrategy(new \Doctrine\ORM\Mapping\UnderscoreNamingStrategy(CASE_UPPER));
        $this->assertInstanceOf('Doctrine\ORM\Mapping\UnderscoreNamingStrategy', $em->getConfiguration()->getNamingStrategy());

        $class = $factory->getMetadataFor('Doctrine\Tests\Models\DDC1476\DDC1476EntityWithDefaultFieldType');

        $this->assertEquals('ID', $class->columnNames['id']);
        $this->assertEquals('NAME', $class->columnNames['name']);
        $this->assertEquals('DDC1476ENTITY_WITH_DEFAULT_FIELD_TYPE', $class->table['name']);
    }

    /**
     * @group DDC-807
     * @group DDC-553
     */
    public function testDiscriminatorColumnDefinition()
    {
        $class = $this->createClassMetadata(__NAMESPACE__ . '\DDC807Entity');

        $this->assertArrayHasKey('columnDefinition', $class->discriminatorColumn);
        $this->assertArrayHasKey('name', $class->discriminatorColumn);

        $this->assertEquals("ENUM('ONE','TWO')", $class->discriminatorColumn['columnDefinition']);
        $this->assertEquals("dtype", $class->discriminatorColumn['name']);
    }

    /**
     * @group DDC-889
     */
    public function testInvalidEntityOrMappedSuperClassShouldMentionParentClasses()
    {
        $this->setExpectedException('Doctrine\ORM\Mapping\MappingException', 'Class "Doctrine\Tests\Models\DDC889\DDC889Class" sub class of "Doctrine\Tests\Models\DDC889\DDC889SuperClass" is not a valid entity or mapped super class.');

        $this->createClassMetadata('Doctrine\Tests\Models\DDC889\DDC889Class');
    }

    /**
     * @group DDC-889
     */
    public function testIdentifierRequiredShouldMentionParentClasses()
    {
        $factory = $this->createClassMetadataFactory();

        $this->setExpectedException('Doctrine\ORM\Mapping\MappingException', 'No identifier/primary key specified for Entity "Doctrine\Tests\Models\DDC889\DDC889Entity" sub class of "Doctrine\Tests\Models\DDC889\DDC889SuperClass". Every Entity must have an identifier/primary key.');
        $factory->getMetadataFor('Doctrine\Tests\Models\DDC889\DDC889Entity');
    }

    public function testNamedQuery()
    {
        $driver = $this->_loadDriver();
        $class = $this->createClassMetadata(__NAMESPACE__.'\User');

        $this->assertCount(1, $class->getNamedQueries(), sprintf("Named queries not processed correctly by driver %s", get_class($driver)));
    }

    /**
     * @group DDC-1663
     */
    public function testNamedNativeQuery()
    {

        $class = $this->createClassMetadata('Doctrine\Tests\Models\CMS\CmsAddress');

        //named native query
        $this->assertCount(3, $class->namedNativeQueries);
        $this->assertArrayHasKey('find-all', $class->namedNativeQueries);
        $this->assertArrayHasKey('find-by-id', $class->namedNativeQueries);


        $findAllQuery = $class->getNamedNativeQuery('find-all');
        $this->assertEquals('find-all', $findAllQuery['name']);
        $this->assertEquals('mapping-find-all', $findAllQuery['resultSetMapping']);
        $this->assertEquals('SELECT id, country, city FROM cms_addresses', $findAllQuery['query']);

        $findByIdQuery = $class->getNamedNativeQuery('find-by-id');
        $this->assertEquals('find-by-id', $findByIdQuery['name']);
        $this->assertEquals('Doctrine\Tests\Models\CMS\CmsAddress',$findByIdQuery['resultClass']);
        $this->assertEquals('SELECT * FROM cms_addresses WHERE id = ?',  $findByIdQuery['query']);

        $countQuery = $class->getNamedNativeQuery('count');
        $this->assertEquals('count', $countQuery['name']);
        $this->assertEquals('mapping-count', $countQuery['resultSetMapping']);
        $this->assertEquals('SELECT COUNT(*) AS count FROM cms_addresses',  $countQuery['query']);

        // result set mapping
        $this->assertCount(3, $class->sqlResultSetMappings);
        $this->assertArrayHasKey('mapping-count', $class->sqlResultSetMappings);
        $this->assertArrayHasKey('mapping-find-all', $class->sqlResultSetMappings);
        $this->assertArrayHasKey('mapping-without-fields', $class->sqlResultSetMappings);

        $findAllMapping = $class->getSqlResultSetMapping('mapping-find-all');
        $this->assertEquals('mapping-find-all', $findAllMapping['name']);
        $this->assertEquals('Doctrine\Tests\Models\CMS\CmsAddress', $findAllMapping['entities'][0]['entityClass']);
        $this->assertEquals(array('name'=>'id','column'=>'id'), $findAllMapping['entities'][0]['fields'][0]);
        $this->assertEquals(array('name'=>'city','column'=>'city'), $findAllMapping['entities'][0]['fields'][1]);
        $this->assertEquals(array('name'=>'country','column'=>'country'), $findAllMapping['entities'][0]['fields'][2]);

        $withoutFieldsMapping = $class->getSqlResultSetMapping('mapping-without-fields');
        $this->assertEquals('mapping-without-fields', $withoutFieldsMapping['name']);
        $this->assertEquals('Doctrine\Tests\Models\CMS\CmsAddress', $withoutFieldsMapping['entities'][0]['entityClass']);
        $this->assertEquals(array(), $withoutFieldsMapping['entities'][0]['fields']);

        $countMapping = $class->getSqlResultSetMapping('mapping-count');
        $this->assertEquals('mapping-count', $countMapping['name']);
        $this->assertEquals(array('name'=>'count'), $countMapping['columns'][0]);

    }

    /**
     * @group DDC-1663
     */
    public function testSqlResultSetMapping()
    {

        $userMetadata   = $this->createClassMetadata('Doctrine\Tests\Models\CMS\CmsUser');
        $personMetadata = $this->createClassMetadata('Doctrine\Tests\Models\Company\CompanyPerson');

        // user asserts
        $this->assertCount(4, $userMetadata->getSqlResultSetMappings());

        $mapping = $userMetadata->getSqlResultSetMapping('mappingJoinedAddress');
        $this->assertEquals(array(),$mapping['columns']);
        $this->assertEquals('mappingJoinedAddress', $mapping['name']);
        $this->assertNull($mapping['entities'][0]['discriminatorColumn']);
        $this->assertEquals(array('name'=>'id','column'=>'id'),                     $mapping['entities'][0]['fields'][0]);
        $this->assertEquals(array('name'=>'name','column'=>'name'),                 $mapping['entities'][0]['fields'][1]);
        $this->assertEquals(array('name'=>'status','column'=>'status'),             $mapping['entities'][0]['fields'][2]);
        $this->assertEquals(array('name'=>'address.zip','column'=>'zip'),           $mapping['entities'][0]['fields'][3]);
        $this->assertEquals(array('name'=>'address.city','column'=>'city'),         $mapping['entities'][0]['fields'][4]);
        $this->assertEquals(array('name'=>'address.country','column'=>'country'),   $mapping['entities'][0]['fields'][5]);
        $this->assertEquals(array('name'=>'address.id','column'=>'a_id'),           $mapping['entities'][0]['fields'][6]);
        $this->assertEquals($userMetadata->name,                                    $mapping['entities'][0]['entityClass']);


        $mapping = $userMetadata->getSqlResultSetMapping('mappingJoinedPhonenumber');
        $this->assertEquals(array(),$mapping['columns']);
        $this->assertEquals('mappingJoinedPhonenumber', $mapping['name']);
        $this->assertNull($mapping['entities'][0]['discriminatorColumn']);
        $this->assertEquals(array('name'=>'id','column'=>'id'),                             $mapping['entities'][0]['fields'][0]);
        $this->assertEquals(array('name'=>'name','column'=>'name'),                         $mapping['entities'][0]['fields'][1]);
        $this->assertEquals(array('name'=>'status','column'=>'status'),                     $mapping['entities'][0]['fields'][2]);
        $this->assertEquals(array('name'=>'phonenumbers.phonenumber','column'=>'number'),   $mapping['entities'][0]['fields'][3]);
        $this->assertEquals($userMetadata->name,                                            $mapping['entities'][0]['entityClass']);

        $mapping = $userMetadata->getSqlResultSetMapping('mappingUserPhonenumberCount');
        $this->assertEquals(array('name'=>'numphones'),$mapping['columns'][0]);
        $this->assertEquals('mappingUserPhonenumberCount', $mapping['name']);
        $this->assertNull($mapping['entities'][0]['discriminatorColumn']);
        $this->assertEquals(array('name'=>'id','column'=>'id'),         $mapping['entities'][0]['fields'][0]);
        $this->assertEquals(array('name'=>'name','column'=>'name'),     $mapping['entities'][0]['fields'][1]);
        $this->assertEquals(array('name'=>'status','column'=>'status'), $mapping['entities'][0]['fields'][2]);
        $this->assertEquals($userMetadata->name,                        $mapping['entities'][0]['entityClass']);

        $mapping = $userMetadata->getSqlResultSetMapping('mappingMultipleJoinsEntityResults');
        $this->assertEquals(array('name'=>'numphones'),$mapping['columns'][0]);
        $this->assertEquals('mappingMultipleJoinsEntityResults', $mapping['name']);
        $this->assertNull($mapping['entities'][0]['discriminatorColumn']);
        $this->assertEquals(array('name'=>'id','column'=>'u_id'),           $mapping['entities'][0]['fields'][0]);
        $this->assertEquals(array('name'=>'name','column'=>'u_name'),       $mapping['entities'][0]['fields'][1]);
        $this->assertEquals(array('name'=>'status','column'=>'u_status'),   $mapping['entities'][0]['fields'][2]);
        $this->assertEquals($userMetadata->name,                            $mapping['entities'][0]['entityClass']);
        $this->assertNull($mapping['entities'][1]['discriminatorColumn']);
        $this->assertEquals(array('name'=>'id','column'=>'a_id'),           $mapping['entities'][1]['fields'][0]);
        $this->assertEquals(array('name'=>'zip','column'=>'a_zip'),         $mapping['entities'][1]['fields'][1]);
        $this->assertEquals(array('name'=>'country','column'=>'a_country'), $mapping['entities'][1]['fields'][2]);
        $this->assertEquals('Doctrine\Tests\Models\CMS\CmsAddress',         $mapping['entities'][1]['entityClass']);

        //person asserts
        $this->assertCount(1, $personMetadata->getSqlResultSetMappings());

        $mapping = $personMetadata->getSqlResultSetMapping('mappingFetchAll');
        $this->assertEquals(array(),$mapping['columns']);
        $this->assertEquals('mappingFetchAll', $mapping['name']);
        $this->assertEquals('discriminator',                            $mapping['entities'][0]['discriminatorColumn']);
        $this->assertEquals(array('name'=>'id','column'=>'id'),         $mapping['entities'][0]['fields'][0]);
        $this->assertEquals(array('name'=>'name','column'=>'name'),     $mapping['entities'][0]['fields'][1]);
        $this->assertEquals($personMetadata->name,                      $mapping['entities'][0]['entityClass']);
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
        $this->assertArrayHasKey('groups', $guestMetadata->associationMappings);
        $this->assertArrayHasKey('groups', $adminMetadata->associationMappings);

        $guestGroups = $guestMetadata->associationMappings['groups'];
        $adminGroups = $adminMetadata->associationMappings['groups'];

        // assert not override attributes
        $this->assertEquals($guestGroups['fieldName'], $adminGroups['fieldName']);
        $this->assertEquals($guestGroups['type'], $adminGroups['type']);
        $this->assertEquals($guestGroups['mappedBy'], $adminGroups['mappedBy']);
        $this->assertEquals($guestGroups['inversedBy'], $adminGroups['inversedBy']);
        $this->assertEquals($guestGroups['isOwningSide'], $adminGroups['isOwningSide']);
        $this->assertEquals($guestGroups['fetch'], $adminGroups['fetch']);
        $this->assertEquals($guestGroups['isCascadeRemove'], $adminGroups['isCascadeRemove']);
        $this->assertEquals($guestGroups['isCascadePersist'], $adminGroups['isCascadePersist']);
        $this->assertEquals($guestGroups['isCascadeRefresh'], $adminGroups['isCascadeRefresh']);
        $this->assertEquals($guestGroups['isCascadeMerge'], $adminGroups['isCascadeMerge']);
        $this->assertEquals($guestGroups['isCascadeDetach'], $adminGroups['isCascadeDetach']);

         // assert not override attributes
        $this->assertEquals('ddc964_users_groups', $guestGroups['joinTable']['name']);
        $this->assertEquals('user_id', $guestGroups['joinTable']['joinColumns'][0]['name']);
        $this->assertEquals('group_id', $guestGroups['joinTable']['inverseJoinColumns'][0]['name']);

        $this->assertEquals(array('user_id'=>'id'), $guestGroups['relationToSourceKeyColumns']);
        $this->assertEquals(array('group_id'=>'id'), $guestGroups['relationToTargetKeyColumns']);
        $this->assertEquals(array('user_id','group_id'), $guestGroups['joinTableColumns']);


        $this->assertEquals('ddc964_users_admingroups', $adminGroups['joinTable']['name']);
        $this->assertEquals('adminuser_id', $adminGroups['joinTable']['joinColumns'][0]['name']);
        $this->assertEquals('admingroup_id', $adminGroups['joinTable']['inverseJoinColumns'][0]['name']);

        $this->assertEquals(array('adminuser_id'=>'id'), $adminGroups['relationToSourceKeyColumns']);
        $this->assertEquals(array('admingroup_id'=>'id'), $adminGroups['relationToTargetKeyColumns']);
        $this->assertEquals(array('adminuser_id','admingroup_id'), $adminGroups['joinTableColumns']);


        // assert address association mappings
        $this->assertArrayHasKey('address', $guestMetadata->associationMappings);
        $this->assertArrayHasKey('address', $adminMetadata->associationMappings);

        $guestAddress = $guestMetadata->associationMappings['address'];
        $adminAddress = $adminMetadata->associationMappings['address'];

        // assert not override attributes
        $this->assertEquals($guestAddress['fieldName'], $adminAddress['fieldName']);
        $this->assertEquals($guestAddress['type'], $adminAddress['type']);
        $this->assertEquals($guestAddress['mappedBy'], $adminAddress['mappedBy']);
        $this->assertEquals($guestAddress['inversedBy'], $adminAddress['inversedBy']);
        $this->assertEquals($guestAddress['isOwningSide'], $adminAddress['isOwningSide']);
        $this->assertEquals($guestAddress['fetch'], $adminAddress['fetch']);
        $this->assertEquals($guestAddress['isCascadeRemove'], $adminAddress['isCascadeRemove']);
        $this->assertEquals($guestAddress['isCascadePersist'], $adminAddress['isCascadePersist']);
        $this->assertEquals($guestAddress['isCascadeRefresh'], $adminAddress['isCascadeRefresh']);
        $this->assertEquals($guestAddress['isCascadeMerge'], $adminAddress['isCascadeMerge']);
        $this->assertEquals($guestAddress['isCascadeDetach'], $adminAddress['isCascadeDetach']);

        // assert override
        $this->assertEquals('address_id', $guestAddress['joinColumns'][0]['name']);
        $this->assertEquals(array('address_id'=>'id'), $guestAddress['sourceToTargetKeyColumns']);
        $this->assertEquals(array('address_id'=>'address_id'), $guestAddress['joinColumnFieldNames']);
        $this->assertEquals(array('id'=>'address_id'), $guestAddress['targetToSourceKeyColumns']);


        $this->assertEquals('adminaddress_id', $adminAddress['joinColumns'][0]['name']);
        $this->assertEquals(array('adminaddress_id'=>'id'), $adminAddress['sourceToTargetKeyColumns']);
        $this->assertEquals(array('adminaddress_id'=>'adminaddress_id'), $adminAddress['joinColumnFieldNames']);
        $this->assertEquals(array('id'=>'adminaddress_id'), $adminAddress['targetToSourceKeyColumns']);
    }

    /**
     * @group DDC-964
     */
    public function testAttributeOverridesMapping()
    {

        $factory       = $this->createClassMetadataFactory();
        $guestMetadata = $factory->getMetadataFor('Doctrine\Tests\Models\DDC964\DDC964Guest');
        $adminMetadata = $factory->getMetadataFor('Doctrine\Tests\Models\DDC964\DDC964Admin');

        $this->assertTrue($adminMetadata->fieldMappings['id']['id']);
        $this->assertEquals('id', $adminMetadata->fieldMappings['id']['fieldName']);
        $this->assertEquals('user_id', $adminMetadata->fieldMappings['id']['columnName']);
        $this->assertEquals(array('user_id'=>'id','user_name'=>'name'), $adminMetadata->fieldNames);
        $this->assertEquals(array('id'=>'user_id','name'=>'user_name'), $adminMetadata->columnNames);
        $this->assertEquals(150, $adminMetadata->fieldMappings['id']['length']);


        $this->assertEquals('name', $adminMetadata->fieldMappings['name']['fieldName']);
        $this->assertEquals('user_name', $adminMetadata->fieldMappings['name']['columnName']);
        $this->assertEquals(250, $adminMetadata->fieldMappings['name']['length']);
        $this->assertTrue($adminMetadata->fieldMappings['name']['nullable']);
        $this->assertFalse($adminMetadata->fieldMappings['name']['unique']);


        $this->assertTrue($guestMetadata->fieldMappings['id']['id']);
        $this->assertEquals('guest_id', $guestMetadata->fieldMappings['id']['columnName']);
        $this->assertEquals('id', $guestMetadata->fieldMappings['id']['fieldName']);
        $this->assertEquals(array('guest_id'=>'id','guest_name'=>'name'), $guestMetadata->fieldNames);
        $this->assertEquals(array('id'=>'guest_id','name'=>'guest_name'), $guestMetadata->columnNames);
        $this->assertEquals(140, $guestMetadata->fieldMappings['id']['length']);

        $this->assertEquals('name', $guestMetadata->fieldMappings['name']['fieldName']);
        $this->assertEquals('guest_name', $guestMetadata->fieldMappings['name']['columnName']);
        $this->assertEquals(240, $guestMetadata->fieldMappings['name']['length']);
        $this->assertFalse($guestMetadata->fieldMappings['name']['nullable']);
        $this->assertTrue($guestMetadata->fieldMappings['name']['unique']);
    }

}

/**
 * @Entity
 * @HasLifecycleCallbacks
 * @Table(
 *  name="cms_users",
 *  uniqueConstraints={@UniqueConstraint(name="search_idx", columns={"name", "user_email"})},
 *  indexes={@Index(name="name_idx", columns={"name"}), @Index(name="0", columns={"user_email"})},
 *  options={"foo": "bar", "baz": {"key": "val"}}
 * )
 * @NamedQueries({@NamedQuery(name="all", query="SELECT u FROM __CLASS__ u")})
 */
class User
{
    /**
     * @Id
     * @Column(type="integer")
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

    public static function loadMetadata(ClassMetadataInfo $metadata)
    {
        $metadata->setInheritanceType(ClassMetadataInfo::INHERITANCE_TYPE_NONE);
        $metadata->setPrimaryTable(array(
           'name' => 'cms_users',
           'options' => array('foo' => 'bar', 'baz' => array('key' => 'val')),
          ));
        $metadata->setChangeTrackingPolicy(ClassMetadataInfo::CHANGETRACKING_DEFERRED_IMPLICIT);
        $metadata->addLifecycleCallback('doStuffOnPrePersist', 'prePersist');
        $metadata->addLifecycleCallback('doOtherStuffOnPrePersistToo', 'prePersist');
        $metadata->addLifecycleCallback('doStuffOnPostPersist', 'postPersist');
        $metadata->mapField(array(
           'id' => true,
           'fieldName' => 'id',
           'type' => 'integer',
           'columnName' => 'id',
          ));
        $metadata->mapField(array(
           'fieldName' => 'name',
           'type' => 'string',
           'length' => 50,
           'unique' => true,
           'nullable' => true,
           'columnName' => 'name',
           'options' => array('foo' => 'bar', 'baz' => array('key' => 'val')),
          ));
        $metadata->mapField(array(
           'fieldName' => 'email',
           'type' => 'string',
           'columnName' => 'user_email',
           'columnDefinition' => 'CHAR(32) NOT NULL',
          ));
        $mapping = array('fieldName' => 'version', 'type' => 'integer');
        $metadata->setVersionMapping($mapping);
        $metadata->mapField($mapping);
        $metadata->setIdGeneratorType(ClassMetadataInfo::GENERATOR_TYPE_AUTO);
        $metadata->mapOneToOne(array(
           'fieldName' => 'address',
           'targetEntity' => 'Doctrine\\Tests\\ORM\\Mapping\\Address',
           'cascade' =>
           array(
           0 => 'remove',
           ),
           'mappedBy' => NULL,
           'inversedBy' => 'user',
           'joinColumns' =>
           array(
           0 =>
           array(
            'name' => 'address_id',
            'referencedColumnName' => 'id',
            'onDelete' => 'CASCADE',
           ),
           ),
           'orphanRemoval' => false,
          ));
        $metadata->mapOneToMany(array(
           'fieldName' => 'phonenumbers',
           'targetEntity' => 'Doctrine\\Tests\\ORM\\Mapping\\Phonenumber',
           'cascade' =>
           array(
           1 => 'persist',
           ),
           'mappedBy' => 'user',
           'orphanRemoval' => true,
           'orderBy' =>
           array(
           'number' => 'ASC',
           ),
          ));
        $metadata->mapManyToMany(array(
           'fieldName' => 'groups',
           'targetEntity' => 'Doctrine\\Tests\\ORM\\Mapping\\Group',
           'cascade' =>
           array(
           0 => 'remove',
           1 => 'persist',
           2 => 'refresh',
           3 => 'merge',
           4 => 'detach',
           ),
           'mappedBy' => NULL,
           'joinTable' =>
           array(
           'name' => 'cms_users_groups',
           'joinColumns' =>
           array(
            0 =>
            array(
            'name' => 'user_id',
            'referencedColumnName' => 'id',
            'unique' => false,
            'nullable' => false,
            ),
           ),
           'inverseJoinColumns' =>
           array(
            0 =>
            array(
            'name' => 'group_id',
            'referencedColumnName' => 'id',
            'columnDefinition' => 'INT NULL',
            ),
           ),
           ),
           'orderBy' => NULL,
          ));
        $metadata->table['uniqueConstraints'] = array(
            'search_idx' => array('columns' => array('name', 'user_email')),
        );
        $metadata->table['indexes'] = array(
            'name_idx' => array('columns' => array('name')), 0 => array('columns' => array('user_email'))
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

    public static function loadMetadata(ClassMetadataInfo $metadata)
    {
        $metadata->setIdGeneratorType(ClassMetadataInfo::GENERATOR_TYPE_CUSTOM);
        $metadata->setCustomGeneratorDefinition(array("class" => "stdClass"));
    }
}

/** @Entity */
class Cat extends Animal
{
    public static function loadMetadata(ClassMetadataInfo $metadata)
    {

    }
}

/** @Entity */
class Dog extends Animal
{
    public static function loadMetadata(ClassMetadataInfo $metadata)
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
    function __construct($value = null)
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
     * @return integer
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

    public static function loadMetadata(ClassMetadataInfo $metadata)
    {
        $metadata->mapField(array(
           'id'                 => true,
           'fieldName'          => 'id',
           'columnDefinition'   => 'INT unsigned NOT NULL',
        ));

        $metadata->mapField(array(
            'fieldName'         => 'value',
            'columnDefinition'  => 'VARCHAR(255) NOT NULL'
        ));

        $metadata->setIdGeneratorType(ClassMetadataInfo::GENERATOR_TYPE_NONE);
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

   public static function loadMetadata(ClassMetadataInfo $metadata)
    {
         $metadata->mapField(array(
           'id'                 => true,
           'fieldName'          => 'id',
        ));

        $metadata->setDiscriminatorColumn(array(
            'name'              => "dtype",
            'type'              => "string",
            'columnDefinition'  => "ENUM('ONE','TWO')"
        ));

        $metadata->setIdGeneratorType(ClassMetadataInfo::GENERATOR_TYPE_NONE);
    }
}


class DDC807SubClasse1 {}
class DDC807SubClasse2 {}

class Address {}
class Phonenumber {}
class Group {}
