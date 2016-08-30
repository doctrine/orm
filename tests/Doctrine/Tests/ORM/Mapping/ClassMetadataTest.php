<?php

namespace Doctrine\Tests\ORM\Mapping;

use Doctrine\Common\Persistence\Mapping\RuntimeReflectionService;
use Doctrine\Common\Persistence\Mapping\StaticReflectionService;
use Doctrine\DBAL\Types\Type;
use Doctrine\ORM\Mapping\ClassMetadata;
use Doctrine\ORM\Events;
use Doctrine\ORM\Mapping\DefaultNamingStrategy;
use Doctrine\ORM\Mapping\DiscriminatorColumnMetadata;
use Doctrine\ORM\Mapping\FieldMetadata;
use Doctrine\ORM\Mapping\JoinColumnMetadata;
use Doctrine\ORM\Mapping\MappingException;
use Doctrine\ORM\Mapping\UnderscoreNamingStrategy;
use Doctrine\ORM\Mapping;
use Doctrine\ORM\Query\AST\Join;
use Doctrine\Tests\OrmTestCase;
use Doctrine\Tests\Proxies\__CG__\Doctrine\Tests\Models\CMS\CmsUser;

require_once __DIR__ . '/../../Models/Global/GlobalNamespaceModel.php';

class ClassMetadataTest extends OrmTestCase
{
    public function testClassMetadataInstanceSerialization()
    {
        $cm = new ClassMetadata('Doctrine\Tests\Models\CMS\CmsUser');
        $cm->initializeReflection(new RuntimeReflectionService());

        // Test initial state
        self::assertTrue(count($cm->getReflectionProperties()) == 0);
        self::assertInstanceOf('ReflectionClass', $cm->reflClass);
        self::assertEquals('Doctrine\Tests\Models\CMS\CmsUser', $cm->name);
        self::assertEquals('Doctrine\Tests\Models\CMS\CmsUser', $cm->rootEntityName);
        self::assertEquals(array(), $cm->subClasses);
        self::assertEquals(array(), $cm->parentClasses);
        self::assertEquals(ClassMetadata::INHERITANCE_TYPE_NONE, $cm->inheritanceType);

        // Customize state
        $discrColumn = new DiscriminatorColumnMetadata();

        $discrColumn->setColumnName('disc');
        $discrColumn->setType(Type::getType('integer'));

        $cm->setInheritanceType(ClassMetadata::INHERITANCE_TYPE_SINGLE_TABLE);
        $cm->setSubclasses(array("One", "Two", "Three"));
        $cm->setParentClasses(array("UserParent"));
        $cm->setCustomRepositoryClass("UserRepository");
        $cm->setDiscriminatorColumn($discrColumn);

        $cm->mapOneToOne(array(
            'fieldName'    => 'phonenumbers',
            'targetEntity' => 'CmsAddress',
            'mappedBy'     => 'foo'
        ));

        $cm->markReadOnly();
        $cm->addNamedQuery(array('name' => 'dql', 'query' => 'foo'));

        self::assertEquals(1, count($cm->associationMappings));

        $serialized = serialize($cm);
        $cm = unserialize($serialized);
        $cm->wakeupReflection(new RuntimeReflectionService());

        // Check state
        self::assertTrue(count($cm->getReflectionProperties()) > 0);
        self::assertInstanceOf('ReflectionClass', $cm->reflClass);
        self::assertEquals('Doctrine\Tests\Models\CMS\CmsUser', $cm->name);
        self::assertEquals('UserParent', $cm->rootEntityName);
        self::assertEquals(
            array(
                'Doctrine\Tests\Models\CMS\One',
                'Doctrine\Tests\Models\CMS\Two',
                'Doctrine\Tests\Models\CMS\Three'
            ),
            $cm->subClasses
        );
        self::assertEquals(array('UserParent'), $cm->parentClasses);
        self::assertEquals('Doctrine\Tests\Models\CMS\UserRepository', $cm->customRepositoryClassName);
        self::assertEquals($discrColumn, $cm->discriminatorColumn);
        self::assertTrue($cm->associationMappings['phonenumbers']['type'] == ClassMetadata::ONE_TO_ONE);
        self::assertEquals(1, count($cm->associationMappings));

        $oneOneMapping = $cm->getAssociationMapping('phonenumbers');

        self::assertTrue($oneOneMapping['fetch'] == ClassMetadata::FETCH_LAZY);
        self::assertEquals('phonenumbers', $oneOneMapping['fieldName']);
        self::assertEquals('Doctrine\Tests\Models\CMS\CmsAddress', $oneOneMapping['targetEntity']);
        self::assertTrue($cm->isReadOnly);
        self::assertEquals(array('dql' => array('name'=>'dql','query'=>'foo','dql'=>'foo')), $cm->namedQueries);
    }

    public function testFieldIsNullable()
    {
        $cm = new ClassMetadata('Doctrine\Tests\Models\CMS\CmsUser');
        $cm->initializeReflection(new RuntimeReflectionService());

        // Explicit Nullable
        $property = $cm->addProperty('status', Type::getType('string'), [
            'nullable' => true,
            'length'   => 50,
        ]);

        self::assertTrue($property->isNullable());

        // Explicit Not Nullable
        $property = $cm->addProperty('username', Type::getType('string'), [
            'nullable' => false,
            'length'   => 50,
        ]);

        self::assertFalse($property->isNullable());

        // Implicit Not Nullable
        $property = $cm->addProperty('name', Type::getType('string'), ['length' => 50]);

        self::assertFalse($property->isNullable(), "By default a field should not be nullable.");
    }

    /**
     * @group DDC-115
     */
    public function testMapAssociationInGlobalNamespace()
    {
        require_once __DIR__."/../../Models/Global/GlobalNamespaceModel.php";

        $cm = new ClassMetadata('DoctrineGlobal_Article');

        $cm->initializeReflection(new RuntimeReflectionService());

        $joinColumns = array();

        $joinColumn = new Mapping\JoinColumnMetadata();

        $joinColumn->setColumnName("bar_id");
        $joinColumn->setReferencedColumnName("id");

        $joinColumns[] = $joinColumn;

        $inverseJoinColumns = array();

        $joinColumn = new Mapping\JoinColumnMetadata();

        $joinColumn->setColumnName("baz_id");
        $joinColumn->setReferencedColumnName("id");

        $inverseJoinColumns[] = $joinColumn;

        $joinTable = array(
            'name'               => 'bar',
            'joinColumns'        => $joinColumns,
            'inverseJoinColumns' => $inverseJoinColumns,
        );

        $cm->mapManyToMany(array(
            'fieldName'    => 'author',
            'targetEntity' => 'DoctrineGlobal_User',
            'joinTable'    => $joinTable,
        ));

        self::assertEquals("DoctrineGlobal_User", $cm->associationMappings['author']['targetEntity']);
    }

    public function testMapManyToManyJoinTableDefaults()
    {
        $cm = new ClassMetadata('Doctrine\Tests\Models\CMS\CmsUser');

        $cm->initializeReflection(new RuntimeReflectionService());

        $cm->mapManyToMany(array(
            'fieldName'    => 'groups',
            'targetEntity' => 'CmsGroup'
        ));

        $assoc = $cm->associationMappings['groups'];

        $joinColumns = array();

        $joinColumn = new Mapping\JoinColumnMetadata();

        $joinColumn->setColumnName("cmsuser_id");
        $joinColumn->setReferencedColumnName("id");
        $joinColumn->setOnDelete("CASCADE");

        $joinColumns[] = $joinColumn;

        $inverseJoinColumns = array();

        $joinColumn = new Mapping\JoinColumnMetadata();

        $joinColumn->setColumnName("cmsgroup_id");
        $joinColumn->setReferencedColumnName("id");
        $joinColumn->setOnDelete("CASCADE");

        $inverseJoinColumns[] = $joinColumn;

        $joinTable = array(
            'name'               => 'cmsuser_cmsgroup',
            'joinColumns'        => $joinColumns,
            'inverseJoinColumns' => $inverseJoinColumns,
        );

        self::assertEquals($joinTable, $assoc['joinTable']);
    }

    public function testSerializeManyToManyJoinTableCascade()
    {
        $cm = new ClassMetadata('Doctrine\Tests\Models\CMS\CmsUser');
        $cm->initializeReflection(new RuntimeReflectionService());

        $cm->mapManyToMany(array(
            'fieldName'    => 'groups',
            'targetEntity' => 'CmsGroup'
        ));

        $assoc = $cm->associationMappings['groups'];
        $assoc = unserialize(serialize($assoc));

        foreach ($assoc['joinTable']['joinColumns'] as $joinColumn) {
            self::assertEquals('CASCADE', $joinColumn->getOnDelete());
        }
    }

    /**
     * @group DDC-115
     */
    public function testSetDiscriminatorMapInGlobalNamespace()
    {
        require_once __DIR__."/../../Models/Global/GlobalNamespaceModel.php";

        $cm = new ClassMetadata('DoctrineGlobal_User');
        $cm->initializeReflection(new RuntimeReflectionService());
        $cm->setDiscriminatorMap(array('descr' => 'DoctrineGlobal_Article', 'foo' => 'DoctrineGlobal_User'));

        self::assertEquals("DoctrineGlobal_Article", $cm->discriminatorMap['descr']);
        self::assertEquals("DoctrineGlobal_User", $cm->discriminatorMap['foo']);
    }

    /**
     * @group DDC-115
     */
    public function testSetSubClassesInGlobalNamespace()
    {
        require_once __DIR__."/../../Models/Global/GlobalNamespaceModel.php";

        $cm = new ClassMetadata('DoctrineGlobal_User');
        $cm->initializeReflection(new RuntimeReflectionService());
        $cm->setSubclasses(array('DoctrineGlobal_Article'));

        self::assertEquals("DoctrineGlobal_Article", $cm->subClasses[0]);
    }

    /**
     * @group DDC-268
     */
    public function testSetInvalidVersionMapping_ThrowsException()
    {
        $metadata = new ClassMetadata('Doctrine\Tests\Models\CMS\CmsUser');
        $property = new FieldMetadata('foo'); //new FieldMetadata('foo', 'foo', Type::getType('string'));

        $property->setDeclaringClass($metadata);
        $property->setColumnName('foo');
        $property->setType(Type::getType('string'));

        $metadata->initializeReflection(new RuntimeReflectionService());

        $this->expectException(\Doctrine\ORM\Mapping\MappingException::class);

        $metadata->setVersionProperty($property);
    }

    public function testGetSingleIdentifierFieldName_MultipleIdentifierEntity_ThrowsException()
    {
        $cm = new ClassMetadata('Doctrine\Tests\Models\CMS\CmsUser');
        $cm->initializeReflection(new RuntimeReflectionService());
        $cm->isIdentifierComposite  = true;

        $this->expectException(\Doctrine\ORM\Mapping\MappingException::class);
        $cm->getSingleIdentifierFieldName();
    }

    public function testDuplicateAssociationMappingException()
    {
        $cm = new ClassMetadata('Doctrine\Tests\Models\CMS\CmsUser');
        $cm->initializeReflection(new RuntimeReflectionService());

        $a1 = array('fieldName' => 'foo', 'sourceEntity' => 'stdClass', 'targetEntity' => 'stdClass', 'mappedBy' => 'foo');
        $a2 = array('fieldName' => 'foo', 'sourceEntity' => 'stdClass', 'targetEntity' => 'stdClass', 'mappedBy' => 'foo');

        $cm->addInheritedAssociationMapping($a1);
        $this->expectException(\Doctrine\ORM\Mapping\MappingException::class);
        $cm->addInheritedAssociationMapping($a2);
    }

    public function testDuplicateColumnName_ThrowsMappingException()
    {
        $cm = new ClassMetadata('Doctrine\Tests\Models\CMS\CmsUser');

        $cm->initializeReflection(new RuntimeReflectionService());

        $cm->addProperty('name', Type::getType('string'));

        $this->expectException(\Doctrine\ORM\Mapping\MappingException::class);

        $cm->addProperty('username', Type::getType('string'), array('columnName' => 'name'));
    }

    public function testDuplicateColumnName_DiscriminatorColumn_ThrowsMappingException()
    {
        $cm = new ClassMetadata('Doctrine\Tests\Models\CMS\CmsUser');
        $cm->initializeReflection(new RuntimeReflectionService());

        $cm->addProperty('name', Type::getType('string'));

        $discrColumn = new DiscriminatorColumnMetadata();

        $discrColumn->setColumnName('name');
        $discrColumn->setType(Type::getType('string'));
        $discrColumn->setLength(255);

        $this->expectException(\Doctrine\ORM\Mapping\MappingException::class);

        $cm->setDiscriminatorColumn($discrColumn);
    }

    public function testDuplicateColumnName_DiscriminatorColumn2_ThrowsMappingException()
    {
        $cm = new ClassMetadata('Doctrine\Tests\Models\CMS\CmsUser');
        $cm->initializeReflection(new RuntimeReflectionService());

        $discrColumn = new DiscriminatorColumnMetadata();

        $discrColumn->setColumnName('name');
        $discrColumn->setType(Type::getType('string'));
        $discrColumn->setLength(255);

        $cm->setDiscriminatorColumn($discrColumn);

        $this->expectException(\Doctrine\ORM\Mapping\MappingException::class);

        $cm->addProperty('name', Type::getType('string'));
    }

    public function testDuplicateFieldAndAssociationMapping1_ThrowsException()
    {
        $cm = new ClassMetadata('Doctrine\Tests\Models\CMS\CmsUser');
        $cm->initializeReflection(new RuntimeReflectionService());

        $cm->addProperty('name', Type::getType('string'));

        $this->expectException(\Doctrine\ORM\Mapping\MappingException::class);

        $cm->mapOneToOne(array(
            'fieldName' => 'name',
            'targetEntity' => 'CmsUser'
        ));
    }

    public function testDuplicateFieldAndAssociationMapping2_ThrowsException()
    {
        $cm = new ClassMetadata('Doctrine\Tests\Models\CMS\CmsUser');
        $cm->initializeReflection(new RuntimeReflectionService());

        $cm->mapOneToOne(array(
            'fieldName' => 'name',
            'targetEntity' => 'CmsUser'
        ));

        $this->expectException(\Doctrine\ORM\Mapping\MappingException::class);

        $cm->addProperty('name', Type::getType('string'));
    }

    /**
     * @group DDC-1224
     */
    public function testGetTemporaryTableNameSchema()
    {
        $cm = new ClassMetadata('Doctrine\Tests\Models\CMS\CmsUser');
        $cm->initializeReflection(new RuntimeReflectionService());

        $cm->setPrimaryTable([
            'schema' => 'foo',
            'name'   => 'bar',
        ]);

        self::assertEquals('foo_bar_id_tmp', $cm->getTemporaryIdTableName());
    }

    public function testDefaultTableName()
    {
        $cm = new ClassMetadata('Doctrine\Tests\Models\CMS\CmsUser');
        $cm->initializeReflection(new RuntimeReflectionService());

        // When table's name is not given
        $primaryTable = array();
        $cm->setPrimaryTable($primaryTable);

        self::assertEquals('CmsUser', $cm->getTableName());
        self::assertEquals('CmsUser', $cm->table['name']);

        $cm = new ClassMetadata('Doctrine\Tests\Models\CMS\CmsAddress');
        $cm->initializeReflection(new RuntimeReflectionService());

        // When joinTable's name is not given
        $joinColumns = array();

        $joinColumn = new Mapping\JoinColumnMetadata();

        $joinColumn->setReferencedColumnName("id");

        $joinColumns[] = $joinColumn;

        $inverseJoinColumns = array();

        $joinColumn = new Mapping\JoinColumnMetadata();

        $joinColumn->setReferencedColumnName("id");

        $inverseJoinColumns[] = $joinColumn;

        $joinTable = array(
            'joinColumns'        => $joinColumns,
            'inverseJoinColumns' => $inverseJoinColumns,
        );

        $cm->mapManyToMany(array(
            'fieldName'    => 'user',
            'targetEntity' => 'CmsUser',
            'inversedBy'   => 'users',
            'joinTable'    => $joinTable,
        ));

        self::assertEquals('cmsaddress_cmsuser', $cm->associationMappings['user']['joinTable']['name']);
    }

    public function testDefaultJoinColumnName()
    {
        $cm = new ClassMetadata('Doctrine\Tests\Models\CMS\CmsAddress');
        $cm->initializeReflection(new RuntimeReflectionService());

        // this is really dirty, but it's the simplest way to test whether
        // joinColumn's name will be automatically set to user_id
        $joinColumns = array();

        $joinColumn = new JoinColumnMetadata();

        $joinColumn->setReferencedColumnName('id');

        $joinColumns[] = $joinColumn;

        $cm->mapOneToOne(array(
            'fieldName'    => 'user',
            'targetEntity' => 'CmsUser',
            'joinColumns'  => $joinColumns,
        ));

        $association = $cm->associationMappings['user'];
        $joinColumn  = reset($association['joinColumns']);

        self::assertEquals('user_id', $joinColumn->getColumnName());

        $cm = new ClassMetadata('Doctrine\Tests\Models\CMS\CmsAddress');

        $cm->initializeReflection(new RuntimeReflectionService());

        $joinColumns = array();

        $joinColumn = new JoinColumnMetadata();

        $joinColumn->setReferencedColumnName('id');

        $joinColumns[] = $joinColumn;

        $inverseJoinColumns = array();

        $joinColumn = new JoinColumnMetadata();

        $joinColumn->setReferencedColumnName('id');

        $inverseJoinColumns[] = $joinColumn;

        $joinTable = array(
            'name'               => 'user_CmsUser',
            'joinColumns'        => $joinColumns,
            'inverseJoinColumns' => $inverseJoinColumns,
        );

        $cm->mapManyToMany(array(
            'fieldName'    => 'user',
            'targetEntity' => 'CmsUser',
            'inversedBy'   => 'users',
            'joinTable'    => $joinTable,
        ));

        $association       = $cm->associationMappings['user'];
        $joinTable         = $association['joinTable'];
        $joinColumn        = reset($joinTable['joinColumns']);
        $inverseJoinColumn = reset($joinTable['joinColumns']);

        self::assertEquals('cmsaddress_id', $joinColumn->getColumnName());
        self::assertEquals('cmsuser_id', $inverseJoinColumn->getColumnName());
    }

    /**
     * @group DDC-559
     */
    public function testOneToOneUnderscoreNamingStrategyDefaults()
    {
        $namingStrategy = new UnderscoreNamingStrategy(CASE_UPPER);
        $metadata       = new ClassMetadata('Doctrine\Tests\Models\CMS\CmsAddress', $namingStrategy);

        $metadata->mapOneToOne(array(
            'fieldName'     => 'user',
            'targetEntity'  => 'CmsUser'
        ));

        $association = $metadata->associationMappings['user'];
        $joinColumn  = reset($association['joinColumns']);

        self::assertEquals(array('USER_ID'=>'ID'), $association['sourceToTargetKeyColumns']);
        self::assertEquals(array('USER_ID'=>'USER_ID'), $association['joinColumnFieldNames']);
        self::assertEquals(array('ID'=>'USER_ID'), $association['targetToSourceKeyColumns']);

        self::assertEquals('USER_ID', $joinColumn->getColumnName());
        self::assertEquals('ID', $joinColumn->getReferencedColumnName());
    }

    /**
     * @group DDC-559
     */
    public function testManyToManyUnderscoreNamingStrategyDefaults()
    {
        $namingStrategy = new UnderscoreNamingStrategy(CASE_UPPER);
        $metadata       = new ClassMetadata('Doctrine\Tests\Models\CMS\CmsAddress', $namingStrategy);

        $metadata->mapManyToMany(array(
            'fieldName'    => 'user',
            'targetEntity' => 'CmsUser'
        ));

        $association       = $metadata->associationMappings['user'];
        $joinColumn        = reset($association['joinTable']['joinColumns']);
        $inverseJoinColumn = reset($association['joinTable']['inverseJoinColumns']);

        self::assertEquals('CMS_ADDRESS_CMS_USER', $association['joinTable']['name']);

        self::assertEquals(array('CMS_ADDRESS_ID'=>'ID'), $association['relationToSourceKeyColumns']);
        self::assertEquals(array('CMS_USER_ID'=>'ID'), $association['relationToTargetKeyColumns']);

        self::assertEquals('CMS_ADDRESS_ID', $joinColumn->getColumnName());
        self::assertEquals('ID', $joinColumn->getReferencedColumnName());

        self::assertEquals('CMS_USER_ID', $inverseJoinColumn->getColumnName());
        self::assertEquals('ID', $inverseJoinColumn->getReferencedColumnName());

        $cm = new ClassMetadata('DoctrineGlobal_Article', $namingStrategy);

        $cm->mapManyToMany(array(
            'fieldName'    => 'author',
            'targetEntity' => 'Doctrine\Tests\Models\CMS\CmsUser'
        ));

        self::assertEquals('DOCTRINE_GLOBAL_ARTICLE_CMS_USER', $cm->associationMappings['author']['joinTable']['name']);
    }

    /**
     * @group DDC-886
     */
    public function testSetMultipleIdentifierSetsComposite()
    {
        $cm = new ClassMetadata('Doctrine\Tests\Models\CMS\CmsUser');
        $cm->initializeReflection(new RuntimeReflectionService());

        $cm->addProperty('name', Type::getType('string'));
        $cm->addProperty('username', Type::getType('string'));

        $cm->setIdentifier(array('name', 'username'));

        self::assertTrue($cm->isIdentifierComposite);
    }

    /**
     * @group DDC-961
     */
    public function testJoinTableMappingDefaults()
    {
        $cm = new ClassMetadata('DoctrineGlobal_Article');

        $cm->initializeReflection(new RuntimeReflectionService());

        $cm->mapManyToMany(array(
            'fieldName'    => 'author',
            'targetEntity' => 'Doctrine\Tests\Models\CMS\CmsUser'
        ));

        self::assertEquals('doctrineglobal_article_cmsuser', $cm->associationMappings['author']['joinTable']['name']);
    }

    /**
     * @group DDC-117
     */
    public function testMapIdentifierAssociation()
    {
        $cm = new ClassMetadata('Doctrine\Tests\Models\DDC117\DDC117ArticleDetails');
        $cm->initializeReflection(new RuntimeReflectionService());

        $cm->mapOneToOne(array(
            'fieldName'    => 'article',
            'id'           => true,
            'targetEntity' => 'Doctrine\Tests\Models\DDC117\DDC117Article',
            'joinColumns'  => array(),
        ));

        self::assertTrue($cm->containsForeignIdentifier, "Identifier Association should set 'containsForeignIdentifier' boolean flag.");
        self::assertEquals(array("article"), $cm->identifier);
    }

    /**
     * @group DDC-117
     */
    public function testOrphanRemovalIdentifierAssociation()
    {
        $cm = new ClassMetadata('Doctrine\Tests\Models\DDC117\DDC117ArticleDetails');
        $cm->initializeReflection(new RuntimeReflectionService());

        $this->expectException(MappingException::class);
        $this->expectExceptionMessage('The orphan removal option is not allowed on an association that');

        $cm->mapOneToOne(array(
            'fieldName'     => 'article',
            'id'            => true,
            'targetEntity'  => 'Doctrine\Tests\Models\DDC117\DDC117Article',
            'orphanRemoval' => true,
            'joinColumns'   => array(),
        ));
    }

    /**
     * @group DDC-117
     */
    public function testInverseIdentifierAssociation()
    {
        $cm = new ClassMetadata('Doctrine\Tests\Models\DDC117\DDC117ArticleDetails');
        $cm->initializeReflection(new RuntimeReflectionService());

        $this->expectException(MappingException::class);
        $this->expectExceptionMessage('An inverse association is not allowed to be identifier in');

        $cm->mapOneToOne(array(
            'fieldName'    => 'article',
            'id'           => true,
            'mappedBy'     => 'details', // INVERSE!
            'targetEntity' => 'Doctrine\Tests\Models\DDC117\DDC117Article',
            'joinColumns'  => array(),
        ));
    }

    /**
     * @group DDC-117
     */
    public function testIdentifierAssociationManyToMany()
    {
        $cm = new ClassMetadata('Doctrine\Tests\Models\DDC117\DDC117ArticleDetails');
        $cm->initializeReflection(new RuntimeReflectionService());

        $this->expectException(MappingException::class);
        $this->expectExceptionMessage('Many-to-many or one-to-many associations are not allowed to be identifier in');

        $cm->mapManyToMany(array(
            'fieldName'    => 'article',
            'id'           => true,
            'targetEntity' => 'Doctrine\Tests\Models\DDC117\DDC117Article',
            'joinColumns'  => array(),
        ));
    }

    /**
     * @group DDC-996
     */
    public function testEmptyFieldNameThrowsException()
    {
        $this->expectException(MappingException::class);
        $this->expectExceptionMessage("The field or association mapping misses the 'fieldName' attribute in entity 'Doctrine\Tests\Models\CMS\CmsUser'.");

        $cm = new ClassMetadata('Doctrine\Tests\Models\CMS\CmsUser');
        $cm->initializeReflection(new RuntimeReflectionService());

        $cm->addProperty('', Type::getType('string'));
    }

    public function testRetrievalOfNamedQueries()
    {
        $cm = new ClassMetadata('Doctrine\Tests\Models\CMS\CmsUser');
        $cm->initializeReflection(new RuntimeReflectionService());

        self::assertEquals(0, count($cm->getNamedQueries()));

        $cm->addNamedQuery(array(
            'name'  => 'userById',
            'query' => 'SELECT u FROM __CLASS__ u WHERE u.id = ?1'
        ));

        self::assertEquals(1, count($cm->getNamedQueries()));
    }

    /**
     * @group DDC-1663
     */
    public function testRetrievalOfResultSetMappings()
    {
        $cm = new ClassMetadata('Doctrine\Tests\Models\CMS\CmsUser');
        $cm->initializeReflection(new RuntimeReflectionService());


        self::assertEquals(0, count($cm->getSqlResultSetMappings()));

        $cm->addSqlResultSetMapping(array(
            'name'      => 'find-all',
            'entities'  => array(
                array(
                    'entityClass'   => 'Doctrine\Tests\Models\CMS\CmsUser',
                ),
            ),
        ));

        self::assertEquals(1, count($cm->getSqlResultSetMappings()));
    }

    public function testExistanceOfNamedQuery()
    {
        $cm = new ClassMetadata('Doctrine\Tests\Models\CMS\CmsUser');
        $cm->initializeReflection(new RuntimeReflectionService());


        $cm->addNamedQuery(array(
            'name'  => 'all',
            'query' => 'SELECT u FROM __CLASS__ u'
        ));

        self::assertTrue($cm->hasNamedQuery('all'));
        self::assertFalse($cm->hasNamedQuery('userById'));
    }

    /**
     * @group DDC-1663
     */
    public function testRetrieveOfNamedNativeQuery()
    {
        $cm = new ClassMetadata('Doctrine\Tests\Models\CMS\CmsUser');
        $cm->initializeReflection(new RuntimeReflectionService());

        $cm->addNamedNativeQuery(array(
            'name'              => 'find-all',
            'query'             => 'SELECT * FROM cms_users',
            'resultSetMapping'  => 'result-mapping-name',
            'resultClass'       => 'Doctrine\Tests\Models\CMS\CmsUser',
        ));

        $cm->addNamedNativeQuery(array(
            'name'              => 'find-by-id',
            'query'             => 'SELECT * FROM cms_users WHERE id = ?',
            'resultClass'       => '__CLASS__',
            'resultSetMapping'  => 'result-mapping-name',
        ));

        $mapping = $cm->getNamedNativeQuery('find-all');
        self::assertEquals('SELECT * FROM cms_users', $mapping['query']);
        self::assertEquals('result-mapping-name', $mapping['resultSetMapping']);
        self::assertEquals('Doctrine\Tests\Models\CMS\CmsUser', $mapping['resultClass']);

        $mapping = $cm->getNamedNativeQuery('find-by-id');
        self::assertEquals('SELECT * FROM cms_users WHERE id = ?', $mapping['query']);
        self::assertEquals('result-mapping-name', $mapping['resultSetMapping']);
        self::assertEquals('Doctrine\Tests\Models\CMS\CmsUser', $mapping['resultClass']);
    }

    /**
     * @group DDC-1663
     */
    public function testRetrieveOfSqlResultSetMapping()
    {
        $cm = new ClassMetadata('Doctrine\Tests\Models\CMS\CmsUser');
        $cm->initializeReflection(new RuntimeReflectionService());

        $cm->addSqlResultSetMapping(array(
            'name'      => 'find-all',
            'entities'  => array(
                array(
                    'entityClass'   => '__CLASS__',
                    'fields'        => array(
                        array(
                            'name'  => 'id',
                            'column'=> 'id'
                        ),
                        array(
                            'name'  => 'name',
                            'column'=> 'name'
                        )
                    )
                ),
                array(
                    'entityClass'   => 'Doctrine\Tests\Models\CMS\CmsEmail',
                    'fields'        => array(
                        array(
                            'name'  => 'id',
                            'column'=> 'id'
                        ),
                        array(
                            'name'  => 'email',
                            'column'=> 'email'
                        )
                    )
                )
            ),
            'columns'   => array(
                array(
                    'name' => 'scalarColumn'
                )
            )
        ));

        $mapping = $cm->getSqlResultSetMapping('find-all');

        self::assertEquals('Doctrine\Tests\Models\CMS\CmsUser', $mapping['entities'][0]['entityClass']);
        self::assertEquals(array('name'=>'id','column'=>'id'), $mapping['entities'][0]['fields'][0]);
        self::assertEquals(array('name'=>'name','column'=>'name'), $mapping['entities'][0]['fields'][1]);

        self::assertEquals('Doctrine\Tests\Models\CMS\CmsEmail', $mapping['entities'][1]['entityClass']);
        self::assertEquals(array('name'=>'id','column'=>'id'), $mapping['entities'][1]['fields'][0]);
        self::assertEquals(array('name'=>'email','column'=>'email'), $mapping['entities'][1]['fields'][1]);

        self::assertEquals('scalarColumn', $mapping['columns'][0]['name']);
    }

    /**
     * @group DDC-1663
     */
    public function testExistanceOfSqlResultSetMapping()
    {
        $cm = new ClassMetadata('Doctrine\Tests\Models\CMS\CmsUser');
        $cm->initializeReflection(new RuntimeReflectionService());

        $cm->addSqlResultSetMapping(array(
            'name'      => 'find-all',
            'entities'  => array(
                array(
                    'entityClass'   => 'Doctrine\Tests\Models\CMS\CmsUser',
                ),
            ),
        ));

        self::assertTrue($cm->hasSqlResultSetMapping('find-all'));
        self::assertFalse($cm->hasSqlResultSetMapping('find-by-id'));
    }

    /**
     * @group DDC-1663
     */
    public function testExistanceOfNamedNativeQuery()
    {
        $cm = new ClassMetadata('Doctrine\Tests\Models\CMS\CmsUser');
        $cm->initializeReflection(new RuntimeReflectionService());


        $cm->addNamedNativeQuery(array(
            'name'              => 'find-all',
            'query'             => 'SELECT * FROM cms_users',
            'resultClass'       => 'Doctrine\Tests\Models\CMS\CmsUser',
            'resultSetMapping'  => 'result-mapping-name'
        ));

        self::assertTrue($cm->hasNamedNativeQuery('find-all'));
        self::assertFalse($cm->hasNamedNativeQuery('find-by-id'));
    }

    public function testRetrieveOfNamedQuery()
    {
        $cm = new ClassMetadata('Doctrine\Tests\Models\CMS\CmsUser');
        $cm->initializeReflection(new RuntimeReflectionService());


        $cm->addNamedQuery(array(
            'name'  => 'userById',
            'query' => 'SELECT u FROM __CLASS__ u WHERE u.id = ?1'
        ));

        self::assertEquals('SELECT u FROM Doctrine\Tests\Models\CMS\CmsUser u WHERE u.id = ?1', $cm->getNamedQuery('userById'));
    }

    /**
     * @group DDC-1663
     */
    public function testRetrievalOfNamedNativeQueries()
    {
        $cm = new ClassMetadata('Doctrine\Tests\Models\CMS\CmsUser');
        $cm->initializeReflection(new RuntimeReflectionService());

        self::assertEquals(0, count($cm->getNamedNativeQueries()));

        $cm->addNamedNativeQuery(array(
            'name'              => 'find-all',
            'query'             => 'SELECT * FROM cms_users',
            'resultClass'       => 'Doctrine\Tests\Models\CMS\CmsUser',
            'resultSetMapping'  => 'result-mapping-name'
        ));

        self::assertEquals(1, count($cm->getNamedNativeQueries()));
    }

    /**
     * @group DDC-2451
     */
    public function testSerializeEntityListeners()
    {
        $metadata = new ClassMetadata('Doctrine\Tests\Models\Company\CompanyContract');

        $metadata->initializeReflection(new RuntimeReflectionService());
        $metadata->addEntityListener(Events::prePersist, 'CompanyContractListener', 'prePersistHandler');
        $metadata->addEntityListener(Events::postPersist, 'CompanyContractListener', 'postPersistHandler');

        $serialize   = serialize($metadata);
        $unserialize = unserialize($serialize);

        self::assertEquals($metadata->entityListeners, $unserialize->entityListeners);
    }

    /**
     * @expectedException \Doctrine\ORM\Mapping\MappingException
     * @expectedExceptionMessage Query named "userById" in "Doctrine\Tests\Models\CMS\CmsUser" was already declared, but it must be declared only once
     */
    public function testNamingCollisionNamedQueryShouldThrowException()
    {
        $cm = new ClassMetadata('Doctrine\Tests\Models\CMS\CmsUser');
        $cm->initializeReflection(new RuntimeReflectionService());

        $cm->addNamedQuery(array(
            'name'  => 'userById',
            'query' => 'SELECT u FROM __CLASS__ u WHERE u.id = ?1'
        ));

        $cm->addNamedQuery(array(
            'name'  => 'userById',
            'query' => 'SELECT u FROM __CLASS__ u WHERE u.id = ?1'
        ));
    }

    /**
     * @group DDC-1663
     *
     * @expectedException \Doctrine\ORM\Mapping\MappingException
     * @expectedExceptionMessage Query named "find-all" in "Doctrine\Tests\Models\CMS\CmsUser" was already declared, but it must be declared only once
     */
    public function testNamingCollisionNamedNativeQueryShouldThrowException()
    {
        $cm = new ClassMetadata('Doctrine\Tests\Models\CMS\CmsUser');
        $cm->initializeReflection(new RuntimeReflectionService());

        $cm->addNamedNativeQuery(array(
            'name'              => 'find-all',
            'query'             => 'SELECT * FROM cms_users',
            'resultClass'       => 'Doctrine\Tests\Models\CMS\CmsUser',
            'resultSetMapping'  => 'result-mapping-name'
        ));

        $cm->addNamedNativeQuery(array(
            'name'              => 'find-all',
            'query'             => 'SELECT * FROM cms_users',
            'resultClass'       => 'Doctrine\Tests\Models\CMS\CmsUser',
            'resultSetMapping'  => 'result-mapping-name'
        ));
    }

    /**
     * @group DDC-1663
     *
     * @expectedException \Doctrine\ORM\Mapping\MappingException
     * @expectedExceptionMessage Result set mapping named "find-all" in "Doctrine\Tests\Models\CMS\CmsUser" was already declared, but it must be declared only once
     */
    public function testNamingCollisionSqlResultSetMappingShouldThrowException()
    {
        $cm = new ClassMetadata('Doctrine\Tests\Models\CMS\CmsUser');
        $cm->initializeReflection(new RuntimeReflectionService());

        $cm->addSqlResultSetMapping(array(
            'name'      => 'find-all',
            'entities'  => array(
                array(
                    'entityClass'   => 'Doctrine\Tests\Models\CMS\CmsUser',
                ),
            ),
        ));

        $cm->addSqlResultSetMapping(array(
            'name'      => 'find-all',
            'entities'  => array(
                array(
                    'entityClass'   => 'Doctrine\Tests\Models\CMS\CmsUser',
                ),
            ),
        ));
    }

    /**
     * @group DDC-1068
     */
    public function testClassCaseSensitivity()
    {
        $user = new CmsUser();
        $cm = new ClassMetadata('DOCTRINE\TESTS\MODELS\CMS\CMSUSER');
        $cm->initializeReflection(new RuntimeReflectionService());

        self::assertEquals('Doctrine\Tests\Models\CMS\CmsUser', $cm->name);
    }

    /**
     * @group DDC-659
     */
    public function testLifecycleCallbackNotFound()
    {
        $cm = new ClassMetadata('Doctrine\Tests\Models\CMS\CmsUser');
        $cm->initializeReflection(new RuntimeReflectionService());
        $cm->addLifecycleCallback('notfound', 'postLoad');

        $this->expectException(MappingException::class);
        $this->expectExceptionMessage("Entity 'Doctrine\Tests\Models\CMS\CmsUser' has no method 'notfound' to be registered as lifecycle callback.");

        $cm->validateLifecycleCallbacks(new RuntimeReflectionService());
    }

    /**
     * @group ImproveErrorMessages
     */
    public function testTargetEntityNotFound()
    {
        $cm = new ClassMetadata('Doctrine\Tests\Models\CMS\CmsUser');

        $cm->initializeReflection(new RuntimeReflectionService());

        $cm->mapManyToOne(array(
            'fieldName' => 'address',
            'targetEntity' => 'UnknownClass'
        ));

        $this->expectException(MappingException::class);
        $this->expectExceptionMessage("The target-entity Doctrine\Tests\Models\CMS\UnknownClass cannot be found in 'Doctrine\Tests\Models\CMS\CmsUser#address'.");

        $cm->validateAssociations();
    }

    /**
     * @group DDC-1663
     *
     * @expectedException \Doctrine\ORM\Mapping\MappingException
     * @expectedExceptionMessage Query name on entity class 'Doctrine\Tests\Models\CMS\CmsUser' is not defined.
     */
    public function testNameIsMandatoryForNamedQueryMappingException()
    {
        $cm = new ClassMetadata('Doctrine\Tests\Models\CMS\CmsUser');
        $cm->initializeReflection(new RuntimeReflectionService());
        $cm->addNamedQuery(array(
            'query' => 'SELECT u FROM Doctrine\Tests\Models\CMS\CmsUser u',
        ));
    }

    /**
     * @group DDC-1663
     *
     * @expectedException \Doctrine\ORM\Mapping\MappingException
     * @expectedExceptionMessage Query name on entity class 'Doctrine\Tests\Models\CMS\CmsUser' is not defined.
     */
    public function testNameIsMandatoryForNameNativeQueryMappingException()
    {
        $cm = new ClassMetadata('Doctrine\Tests\Models\CMS\CmsUser');
        $cm->initializeReflection(new RuntimeReflectionService());
        $cm->addNamedQuery(array(
            'query'             => 'SELECT * FROM cms_users',
            'resultClass'       => 'Doctrine\Tests\Models\CMS\CmsUser',
            'resultSetMapping'  => 'result-mapping-name'
        ));
    }

    /**
     * @group DDC-1663
     *
     * @expectedException \Doctrine\ORM\Mapping\MappingException
     * @expectedExceptionMessage Result set mapping named "find-all" in "Doctrine\Tests\Models\CMS\CmsUser requires a entity class name.
     */
    public function testNameIsMandatoryForEntityNameSqlResultSetMappingException()
    {
        $cm = new ClassMetadata('Doctrine\Tests\Models\CMS\CmsUser');
        $cm->initializeReflection(new RuntimeReflectionService());
        $cm->addSqlResultSetMapping(array(
            'name'      => 'find-all',
            'entities'  => array(
                array(
                    'fields' => array()
                )
            ),
        ));
    }

    /**
     * @group DDC-984
     * @group DDC-559
     * @group DDC-1575
     */
    public function testFullyQualifiedClassNameShouldBeGivenToNamingStrategy()
    {
        $namingStrategy     = new MyNamespacedNamingStrategy();
        $addressMetadata    = new ClassMetadata('Doctrine\Tests\Models\CMS\CmsAddress', $namingStrategy);
        $articleMetadata    = new ClassMetadata('DoctrineGlobal_Article', $namingStrategy);
        $routingMetadata    = new ClassMetadata('Doctrine\Tests\Models\Routing\RoutingLeg',$namingStrategy);

        $addressMetadata->initializeReflection(new RuntimeReflectionService());
        $articleMetadata->initializeReflection(new RuntimeReflectionService());
        $routingMetadata->initializeReflection(new RuntimeReflectionService());

        $addressMetadata->mapManyToMany(array(
            'fieldName'    => 'user',
            'targetEntity' => 'CmsUser'
        ));

        $articleMetadata->mapManyToMany(array(
            'fieldName'    => 'author',
            'targetEntity' => 'Doctrine\Tests\Models\CMS\CmsUser'
        ));

        self::assertEquals('routing_routingleg', $routingMetadata->table['name']);
        self::assertEquals('cms_cmsaddress_cms_cmsuser', $addressMetadata->associationMappings['user']['joinTable']['name']);
        self::assertEquals('doctrineglobal_article_cms_cmsuser', $articleMetadata->associationMappings['author']['joinTable']['name']);
    }

    /**
     * @group DDC-984
     * @group DDC-559
     */
    public function testFullyQualifiedClassNameShouldBeGivenToNamingStrategyPropertyToColumnName()
    {
        $namingStrategy = new MyPrefixNamingStrategy();
        $metadata       = new ClassMetadata('Doctrine\Tests\Models\CMS\CmsAddress', $namingStrategy);

        $metadata->initializeReflection(new RuntimeReflectionService());

        $metadata->addProperty('country', Type::getType('string'));
        $metadata->addProperty('city', Type::getType('string'));

        self::assertEquals($metadata->fieldNames, array(
            'cmsaddress_country'   => 'country',
            'cmsaddress_city'      => 'city'
        ));
    }

    /**
     * @group DDC-1746
     */
    public function testInvalidCascade()
    {
        $cm = new ClassMetadata('Doctrine\Tests\Models\CMS\CmsUser');

        $cm->initializeReflection(new RuntimeReflectionService());

        $this->expectException(MappingException::class);
        $this->expectExceptionMessage("You have specified invalid cascade options for Doctrine\Tests\Models\CMS\CmsUser::\$address: 'invalid'; available options: 'remove', 'persist', 'refresh', 'merge', and 'detach'");

        $cm->mapManyToOne(array(
            'fieldName' => 'address',
            'targetEntity' => 'UnknownClass',
            'cascade' => array('invalid')
        ));
     }

    /**
     * @group DDC-964
     * @expectedException        Doctrine\ORM\Mapping\MappingException
     * @expectedExceptionMessage Invalid field override named 'invalidPropertyName' for class 'Doctrine\Tests\Models\DDC964\DDC964Admin
     */
    public function testInvalidPropertyAssociationOverrideNameException()
    {
        $cm = new ClassMetadata('Doctrine\Tests\Models\DDC964\DDC964Admin');

        $cm->initializeReflection(new RuntimeReflectionService());

        $cm->mapManyToOne(array(
            'fieldName' => 'address',
            'targetEntity' => 'DDC964Address'
        ));

        $cm->setAssociationOverride('invalidPropertyName', array());
    }

    /**
     * @group DDC-964
     * @expectedException        Doctrine\ORM\Mapping\MappingException
     * @expectedExceptionMessage Invalid field override named 'invalidPropertyName' for class 'Doctrine\Tests\Models\DDC964\DDC964Guest'.
     */
    public function testInvalidPropertyAttributeOverrideNameException()
    {
        $cm = new ClassMetadata('Doctrine\Tests\Models\DDC964\DDC964Guest');

        $cm->initializeReflection(new RuntimeReflectionService());

        $cm->addProperty('name', Type::getType('string'));

        $cm->setAttributeOverride('invalidPropertyName', array());
    }

    /**
     * @group DDC-1955
     *
     * @expectedException        Doctrine\ORM\Mapping\MappingException
     * @expectedExceptionMessage Entity Listener "\InvalidClassName" declared on "Doctrine\Tests\Models\CMS\CmsUser" not found.
     */
    public function testInvalidEntityListenerClassException()
    {
        $cm = new ClassMetadata('Doctrine\Tests\Models\CMS\CmsUser');
        $cm->initializeReflection(new RuntimeReflectionService());

        $cm->addEntityListener(Events::postLoad, '\InvalidClassName', 'postLoadHandler');
    }

    /**
     * @group DDC-1955
     *
     * @expectedException        Doctrine\ORM\Mapping\MappingException
     * @expectedExceptionMessage Entity Listener "\Doctrine\Tests\Models\Company\CompanyContractListener" declared on "Doctrine\Tests\Models\CMS\CmsUser" has no method "invalidMethod".
     */
    public function testInvalidEntityListenerMethodException()
    {
        $cm = new ClassMetadata('Doctrine\Tests\Models\CMS\CmsUser');
        $cm->initializeReflection(new RuntimeReflectionService());

        $cm->addEntityListener(Events::postLoad, '\Doctrine\Tests\Models\Company\CompanyContractListener', 'invalidMethod');
    }

    public function testManyToManySelfReferencingNamingStrategyDefaults()
    {
        $cm = new ClassMetadata('Doctrine\Tests\Models\CustomType\CustomTypeParent');

        $cm->initializeReflection(new RuntimeReflectionService());

        $cm->mapManyToMany(array(
            'fieldName' => 'friendsWithMe',
            'targetEntity' => 'CustomTypeParent'
        ));

        $association = $cm->associationMappings['friendsWithMe'];

        $joinColumns = array();

        $joinColumn = new Mapping\JoinColumnMetadata();

        $joinColumn->setColumnName("customtypeparent_source");
        $joinColumn->setReferencedColumnName("id");
        $joinColumn->setOnDelete("CASCADE");

        $joinColumns[] = $joinColumn;

        $inverseJoinColumns = array();

        $joinColumn = new Mapping\JoinColumnMetadata();

        $joinColumn->setColumnName("customtypeparent_target");
        $joinColumn->setReferencedColumnName("id");
        $joinColumn->setOnDelete("CASCADE");

        $inverseJoinColumns[] = $joinColumn;

        $joinTable = array(
            'name'               => 'customtypeparent_customtypeparent',
            'joinColumns'        => $joinColumns,
            'inverseJoinColumns' => $inverseJoinColumns,
        );

        self::assertEquals($joinTable, $association['joinTable']);
        self::assertEquals(array('customtypeparent_source' => 'id'), $association['relationToSourceKeyColumns']);
        self::assertEquals(array('customtypeparent_target' => 'id'), $association['relationToTargetKeyColumns']);
    }

    /**
     * @group DDC-2608
     */
    public function testSetSequenceGeneratorThrowsExceptionWhenSequenceNameIsMissing()
    {
        $cm = new ClassMetadata('Doctrine\Tests\Models\CMS\CmsUser');

        $cm->setIdGeneratorType(ClassMetadata::GENERATOR_TYPE_SEQUENCE);
        $cm->initializeReflection(new RuntimeReflectionService());

        $this->expectException(\Doctrine\ORM\Mapping\MappingException::class);

        $cm->setGeneratorDefinition(array());
    }

    /**
     * @group DDC-2662
     */
    public function testQuotedSequenceName()
    {
        $cm = new ClassMetadata('Doctrine\Tests\Models\CMS\CmsUser');
        $cm->initializeReflection(new RuntimeReflectionService());

        $cm->setGeneratorDefinition(array('sequenceName' => 'foo', 'allocationSize' => 1));

        self::assertEquals(array('sequenceName' => 'foo', 'allocationSize' => 1), $cm->generatorDefinition);
    }

    /**
     * @group DDC-2700
     */
    public function testIsIdentifierMappedSuperClass()
    {
        $class = new ClassMetadata(__NAMESPACE__ . '\\DDC2700MappedSuperClass');

        self::assertFalse($class->isIdentifier('foo'));
    }

    /**
     * @group DDC-3120
     */
    public function testCanInstantiateInternalPhpClassSubclass()
    {
        $classMetadata = new ClassMetadata(__NAMESPACE__ . '\\MyArrayObjectEntity');

        self::assertInstanceOf(__NAMESPACE__ . '\\MyArrayObjectEntity', $classMetadata->newInstance());
    }

    /**
     * @group DDC-3120
     */
    public function testCanInstantiateInternalPhpClassSubclassFromUnserializedMetadata()
    {
        /* @var $classMetadata ClassMetadata */
        $classMetadata = unserialize(serialize(new ClassMetadata(__NAMESPACE__ . '\\MyArrayObjectEntity')));

        $classMetadata->wakeupReflection(new RuntimeReflectionService());

        self::assertInstanceOf(__NAMESPACE__ . '\\MyArrayObjectEntity', $classMetadata->newInstance());
    }

    /**
     * @group embedded
     */
    public function testWakeupReflectionWithEmbeddableAndStaticReflectionService()
    {
        $classMetadata = new ClassMetadata('Doctrine\Tests\ORM\Mapping\TestEntity1');

        $classMetadata->mapEmbedded(array(
            'fieldName'    => 'test',
            'class'        => 'Doctrine\Tests\ORM\Mapping\TestEntity1',
            'columnPrefix' => false,
        ));

        $mapping = array(
            'originalClass' => 'Doctrine\Tests\ORM\Mapping\TestEntity1',
            'declaredField' => 'test',
            'originalField' => 'embeddedProperty'
        );

        $classMetadata->addProperty('test.embeddedProperty', Type::getType('string'), $mapping);

        $classMetadata->wakeupReflection(new StaticReflectionService());

        self::assertEquals(
            array('test' => null, 'test.embeddedProperty' => null),
            $classMetadata->getReflectionProperties()
        );
    }

    public function testGetColumnNamesWithGivenFieldNames()
    {
        $metadata = new ClassMetadata('Doctrine\Tests\Models\CMS\CmsUser');
        $metadata->initializeReflection(new RuntimeReflectionService());

        $metadata->addProperty('status', Type::getType('string'), ['columnName' => 'foo']);
        $metadata->addProperty('username', Type::getType('string'), ['columnName' => 'bar']);
        $metadata->addProperty('name', Type::getType('string'), ['columnName' => 'baz']);

        self::assertSame(['foo', 'baz'], $metadata->getColumnNames(['status', 'name']));
    }
}

/**
 * @MappedSuperclass
 */
class DDC2700MappedSuperClass
{
    /** @Column */
    private $foo;
}

class MyNamespacedNamingStrategy extends DefaultNamingStrategy
{
    /**
     * {@inheritdoc}
     */
    public function classToTableName($className)
    {
        if (strpos($className, '\\') !== false) {
            $className = str_replace('\\', '_', str_replace('Doctrine\Tests\Models\\', '', $className));
        }

        return strtolower($className);
    }
}

class MyPrefixNamingStrategy extends DefaultNamingStrategy
{
    /**
     * {@inheritdoc}
     */
    public function propertyToColumnName($propertyName, $className = null)
    {
        return strtolower($this->classToTableName($className)) . '_' . $propertyName;
    }
}

class MyArrayObjectEntity extends \ArrayObject
{
}
