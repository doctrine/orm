<?php

namespace Doctrine\Tests\ORM\Mapping;

use Doctrine\Common\Persistence\Mapping\RuntimeReflectionService;
use Doctrine\Common\Persistence\Mapping\StaticReflectionService;
use Doctrine\ORM\Events;
use Doctrine\ORM\Mapping\ClassMetadata;
use Doctrine\ORM\Mapping\DefaultNamingStrategy;
use Doctrine\ORM\Mapping\MappingException;
use Doctrine\ORM\Mapping\UnderscoreNamingStrategy;
use Doctrine\Tests\Models\CMS;
use Doctrine\Tests\Models\Company\CompanyContract;
use Doctrine\Tests\Models\CustomType\CustomTypeParent;
use Doctrine\Tests\Models\DDC117\DDC117Article;
use Doctrine\Tests\Models\DDC117\DDC117ArticleDetails;
use Doctrine\Tests\Models\DDC6412\DDC6412File;
use Doctrine\Tests\Models\DDC964\DDC964Admin;
use Doctrine\Tests\Models\DDC964\DDC964Guest;
use Doctrine\Tests\Models\Routing\RoutingLeg;
use Doctrine\Tests\OrmTestCase;
use DoctrineGlobal_Article;

require_once __DIR__ . '/../../Models/Global/GlobalNamespaceModel.php';

class ClassMetadataTest extends OrmTestCase
{
    public function testClassMetadataInstanceSerialization()
    {
        $cm = new ClassMetadata(CMS\CmsUser::class);
        $cm->initializeReflection(new RuntimeReflectionService());

        // Test initial state
        $this->assertTrue(count($cm->getReflectionProperties()) == 0);
        $this->assertInstanceOf('ReflectionClass', $cm->reflClass);
        $this->assertEquals(CMS\CmsUser::class, $cm->name);
        $this->assertEquals(CMS\CmsUser::class, $cm->rootEntityName);
        $this->assertEquals([], $cm->subClasses);
        $this->assertEquals([], $cm->parentClasses);
        $this->assertEquals(ClassMetadata::INHERITANCE_TYPE_NONE, $cm->inheritanceType);

        // Customize state
        $cm->setInheritanceType(ClassMetadata::INHERITANCE_TYPE_SINGLE_TABLE);
        $cm->setSubclasses(["One", "Two", "Three"]);
        $cm->setParentClasses(["UserParent"]);
        $cm->setCustomRepositoryClass("UserRepository");
        $cm->setDiscriminatorColumn(['name' => 'disc', 'type' => 'integer']);
        $cm->mapOneToOne(['fieldName' => 'phonenumbers', 'targetEntity' => 'CmsAddress', 'mappedBy' => 'foo']);
        $cm->markReadOnly();
        $cm->addNamedQuery(['name' => 'dql', 'query' => 'foo']);
        $this->assertEquals(1, count($cm->associationMappings));

        $serialized = serialize($cm);
        $cm = unserialize($serialized);
        $cm->wakeupReflection(new RuntimeReflectionService());

        // Check state
        $this->assertTrue(count($cm->getReflectionProperties()) > 0);
        $this->assertEquals('Doctrine\Tests\Models\CMS', $cm->namespace);
        $this->assertInstanceOf(\ReflectionClass::class, $cm->reflClass);
        $this->assertEquals(CMS\CmsUser::class, $cm->name);
        $this->assertEquals('UserParent', $cm->rootEntityName);
        $this->assertEquals([CMS\One::class, CMS\Two::class, CMS\Three::class], $cm->subClasses);
        $this->assertEquals(['UserParent'], $cm->parentClasses);
        $this->assertEquals(CMS\UserRepository::class, $cm->customRepositoryClassName);
        $this->assertEquals(['name' => 'disc', 'type' => 'integer', 'fieldName' => 'disc'], $cm->discriminatorColumn);
        $this->assertTrue($cm->associationMappings['phonenumbers']['type'] == ClassMetadata::ONE_TO_ONE);
        $this->assertEquals(1, count($cm->associationMappings));
        $oneOneMapping = $cm->getAssociationMapping('phonenumbers');
        $this->assertTrue($oneOneMapping['fetch'] == ClassMetadata::FETCH_LAZY);
        $this->assertEquals('phonenumbers', $oneOneMapping['fieldName']);
        $this->assertEquals(CMS\CmsAddress::class, $oneOneMapping['targetEntity']);
        $this->assertTrue($cm->isReadOnly);
        $this->assertEquals(['dql' => ['name'=>'dql','query'=>'foo','dql'=>'foo']], $cm->namedQueries);
    }

    public function testFieldIsNullable()
    {
        $cm = new ClassMetadata(CMS\CmsUser::class);
        $cm->initializeReflection(new RuntimeReflectionService());

        // Explicit Nullable
        $cm->mapField(['fieldName' => 'status', 'nullable' => true, 'type' => 'string', 'length' => 50]);
        $this->assertTrue($cm->isNullable('status'));

        // Explicit Not Nullable
        $cm->mapField(['fieldName' => 'username', 'nullable' => false, 'type' => 'string', 'length' => 50]);
        $this->assertFalse($cm->isNullable('username'));

        // Implicit Not Nullable
        $cm->mapField(['fieldName' => 'name', 'type' => 'string', 'length' => 50]);
        $this->assertFalse($cm->isNullable('name'), "By default a field should not be nullable.");
    }

    /**
     * @group DDC-115
     */
    public function testMapAssociationInGlobalNamespace()
    {
        require_once __DIR__."/../../Models/Global/GlobalNamespaceModel.php";

        $cm = new ClassMetadata('DoctrineGlobal_Article');
        $cm->initializeReflection(new RuntimeReflectionService());
        $cm->mapManyToMany(
            [
            'fieldName' => 'author',
            'targetEntity' => 'DoctrineGlobal_User',
            'joinTable' => [
                'name' => 'bar',
                'joinColumns' => [['name' => 'bar_id', 'referencedColumnName' => 'id']],
                'inverseJoinColumns' => [['name' => 'baz_id', 'referencedColumnName' => 'id']],
            ],
            ]
        );

        $this->assertEquals("DoctrineGlobal_User", $cm->associationMappings['author']['targetEntity']);
    }

    public function testMapManyToManyJoinTableDefaults()
    {
        $cm = new ClassMetadata(CMS\CmsUser::class);
        $cm->initializeReflection(new RuntimeReflectionService());
        $cm->mapManyToMany(
            [
            'fieldName' => 'groups',
            'targetEntity' => 'CmsGroup'
            ]
        );

        $assoc = $cm->associationMappings['groups'];
        $this->assertEquals(
            [
            'name' => 'cmsuser_cmsgroup',
            'joinColumns' => [['name' => 'cmsuser_id', 'referencedColumnName' => 'id', 'onDelete' => 'CASCADE']],
            'inverseJoinColumns' => [['name' => 'cmsgroup_id', 'referencedColumnName' => 'id', 'onDelete' => 'CASCADE']]
            ], $assoc['joinTable']);
        $this->assertTrue($assoc['isOnDeleteCascade']);
    }

    public function testSerializeManyToManyJoinTableCascade()
    {
        $cm = new ClassMetadata(CMS\CmsUser::class);
        $cm->initializeReflection(new RuntimeReflectionService());
        $cm->mapManyToMany(
            [
            'fieldName' => 'groups',
            'targetEntity' => 'CmsGroup'
            ]
        );

        /* @var $assoc \Doctrine\ORM\Mapping\ManyToMany */
        $assoc = $cm->associationMappings['groups'];
        $assoc = unserialize(serialize($assoc));

        $this->assertTrue($assoc['isOnDeleteCascade']);
    }

    /**
     * @group DDC-115
     */
    public function testSetDiscriminatorMapInGlobalNamespace()
    {
        require_once __DIR__."/../../Models/Global/GlobalNamespaceModel.php";

        $cm = new ClassMetadata('DoctrineGlobal_User');
        $cm->initializeReflection(new RuntimeReflectionService());
        $cm->setDiscriminatorMap(['descr' => 'DoctrineGlobal_Article', 'foo' => 'DoctrineGlobal_User']);

        $this->assertEquals("DoctrineGlobal_Article", $cm->discriminatorMap['descr']);
        $this->assertEquals("DoctrineGlobal_User", $cm->discriminatorMap['foo']);
    }

    /**
     * @group DDC-115
     */
    public function testSetSubClassesInGlobalNamespace()
    {
        require_once __DIR__."/../../Models/Global/GlobalNamespaceModel.php";

        $cm = new ClassMetadata('DoctrineGlobal_User');
        $cm->initializeReflection(new RuntimeReflectionService());
        $cm->setSubclasses(['DoctrineGlobal_Article']);

        $this->assertEquals("DoctrineGlobal_Article", $cm->subClasses[0]);
    }

    /**
     * @group DDC-268
     */
    public function testSetInvalidVersionMapping_ThrowsException()
    {
        $field = [];
        $field['fieldName'] = 'foo';
        $field['type'] = 'string';

        $cm = new ClassMetadata(CMS\CmsUser::class);
        $cm->initializeReflection(new RuntimeReflectionService());

        $this->expectException(\Doctrine\ORM\Mapping\MappingException::class);
        $cm->setVersionMapping($field);
    }

    public function testGetSingleIdentifierFieldName_MultipleIdentifierEntity_ThrowsException()
    {
        $cm = new ClassMetadata(CMS\CmsUser::class);
        $cm->initializeReflection(new RuntimeReflectionService());
        $cm->isIdentifierComposite  = true;

        $this->expectException(\Doctrine\ORM\Mapping\MappingException::class);
        $cm->getSingleIdentifierFieldName();
    }

    public function testGetSingleIdentifierFieldName_NoIdEntity_ThrowsException()
    {
        $cm = new ClassMetadata(DDC6412File::class);
        $cm->initializeReflection(new RuntimeReflectionService());

        $this->expectException(\Doctrine\ORM\Mapping\MappingException::class);
        $cm->getSingleIdentifierFieldName();
    }

    public function testDuplicateAssociationMappingException()
    {
        $cm = new ClassMetadata(CMS\CmsUser::class);
        $cm->initializeReflection(new RuntimeReflectionService());

        $a1 = ['fieldName' => 'foo', 'sourceEntity' => 'stdClass', 'targetEntity' => 'stdClass', 'mappedBy' => 'foo'];
        $a2 = ['fieldName' => 'foo', 'sourceEntity' => 'stdClass', 'targetEntity' => 'stdClass', 'mappedBy' => 'foo'];

        $cm->addInheritedAssociationMapping($a1);
        $this->expectException(\Doctrine\ORM\Mapping\MappingException::class);
        $cm->addInheritedAssociationMapping($a2);
    }

    public function testDuplicateColumnName_ThrowsMappingException()
    {
        $cm = new ClassMetadata(CMS\CmsUser::class);
        $cm->initializeReflection(new RuntimeReflectionService());

        $cm->mapField(['fieldName' => 'name', 'columnName' => 'name']);

        $this->expectException(\Doctrine\ORM\Mapping\MappingException::class);
        $cm->mapField(['fieldName' => 'username', 'columnName' => 'name']);
    }

    public function testDuplicateColumnName_DiscriminatorColumn_ThrowsMappingException()
    {
        $cm = new ClassMetadata(CMS\CmsUser::class);
        $cm->initializeReflection(new RuntimeReflectionService());

        $cm->mapField(['fieldName' => 'name', 'columnName' => 'name']);

        $this->expectException(\Doctrine\ORM\Mapping\MappingException::class);
        $cm->setDiscriminatorColumn(['name' => 'name']);
    }

    public function testDuplicateColumnName_DiscriminatorColumn2_ThrowsMappingException()
    {
        $cm = new ClassMetadata(CMS\CmsUser::class);
        $cm->initializeReflection(new RuntimeReflectionService());

        $cm->setDiscriminatorColumn(['name' => 'name']);

        $this->expectException(\Doctrine\ORM\Mapping\MappingException::class);
        $cm->mapField(['fieldName' => 'name', 'columnName' => 'name']);
    }

    public function testDuplicateFieldAndAssociationMapping1_ThrowsException()
    {
        $cm = new ClassMetadata(CMS\CmsUser::class);
        $cm->initializeReflection(new RuntimeReflectionService());

        $cm->mapField(['fieldName' => 'name', 'columnName' => 'name']);

        $this->expectException(\Doctrine\ORM\Mapping\MappingException::class);
        $cm->mapOneToOne(['fieldName' => 'name', 'targetEntity' => 'CmsUser']);
    }

    public function testDuplicateFieldAndAssociationMapping2_ThrowsException()
    {
        $cm = new ClassMetadata(CMS\CmsUser::class);
        $cm->initializeReflection(new RuntimeReflectionService());

        $cm->mapOneToOne(['fieldName' => 'name', 'targetEntity' => 'CmsUser']);

        $this->expectException(\Doctrine\ORM\Mapping\MappingException::class);
        $cm->mapField(['fieldName' => 'name', 'columnName' => 'name']);
    }

    /**
     * @group DDC-1224
     */
    public function testGetTemporaryTableNameSchema()
    {
        $cm = new ClassMetadata(CMS\CmsUser::class);
        $cm->initializeReflection(new RuntimeReflectionService());

        $cm->setTableName('foo.bar');

        $this->assertEquals('foo_bar_id_tmp', $cm->getTemporaryIdTableName());
    }

    public function testDefaultTableName()
    {
        $cm = new ClassMetadata(CMS\CmsUser::class);
        $cm->initializeReflection(new RuntimeReflectionService());

        // When table's name is not given
        $primaryTable = [];
        $cm->setPrimaryTable($primaryTable);

        $this->assertEquals('CmsUser', $cm->getTableName());
        $this->assertEquals('CmsUser', $cm->table['name']);

        $cm = new ClassMetadata(CMS\CmsAddress::class);
        $cm->initializeReflection(new RuntimeReflectionService());
        // When joinTable's name is not given
        $cm->mapManyToMany(
            [
                'fieldName' => 'user',
                'targetEntity' => 'CmsUser',
                'inversedBy' => 'users',
                'joinTable' => [
                    'joinColumns' => [['referencedColumnName' => 'id']],
                    'inverseJoinColumns' => [['referencedColumnName' => 'id']]
                ]
            ]
        );
        $this->assertEquals('cmsaddress_cmsuser', $cm->associationMappings['user']['joinTable']['name']);
    }

    public function testDefaultJoinColumnName()
    {
        $cm = new ClassMetadata(CMS\CmsAddress::class);
        $cm->initializeReflection(new RuntimeReflectionService());

        // this is really dirty, but it's the simplest way to test whether
        // joinColumn's name will be automatically set to user_id
        $cm->mapOneToOne(
            [
            'fieldName' => 'user',
            'targetEntity' => 'CmsUser',
            'joinColumns' => [['referencedColumnName' => 'id']]
            ]
        );
        $this->assertEquals('user_id', $cm->associationMappings['user']['joinColumns'][0]['name']);

        $cm = new ClassMetadata(CMS\CmsAddress::class);
        $cm->initializeReflection(new RuntimeReflectionService());
        $cm->mapManyToMany(
            [
            'fieldName' => 'user',
            'targetEntity' => 'CmsUser',
            'inversedBy' => 'users',
            'joinTable' => [
                    'name' => 'user_CmsUser',
                    'joinColumns' => [['referencedColumnName' => 'id']],
                    'inverseJoinColumns' => [['referencedColumnName' => 'id']]
                ]
            ]
        );
        $this->assertEquals('cmsaddress_id', $cm->associationMappings['user']['joinTable']['joinColumns'][0]['name']);
        $this->assertEquals('cmsuser_id', $cm->associationMappings['user']['joinTable']['inverseJoinColumns'][0]['name']);
    }

    /**
     * @group DDC-559
     */
    public function testUnderscoreNamingStrategyDefaults()
    {
        $namingStrategy     = new UnderscoreNamingStrategy(CASE_UPPER);
        $oneToOneMetadata   = new ClassMetadata(CMS\CmsAddress::class, $namingStrategy);
        $manyToManyMetadata = new ClassMetadata(CMS\CmsAddress::class, $namingStrategy);

        $oneToOneMetadata->mapOneToOne(
            [
            'fieldName'     => 'user',
            'targetEntity'  => 'CmsUser'
            ]
        );

        $manyToManyMetadata->mapManyToMany(
            [
            'fieldName'     => 'user',
            'targetEntity'  => 'CmsUser'
            ]
        );

        $this->assertEquals(['USER_ID'=>'ID'], $oneToOneMetadata->associationMappings['user']['sourceToTargetKeyColumns']);
        $this->assertEquals(['USER_ID'=>'USER_ID'], $oneToOneMetadata->associationMappings['user']['joinColumnFieldNames']);
        $this->assertEquals(['ID'=>'USER_ID'], $oneToOneMetadata->associationMappings['user']['targetToSourceKeyColumns']);

        $this->assertEquals('USER_ID', $oneToOneMetadata->associationMappings['user']['joinColumns'][0]['name']);
        $this->assertEquals('ID', $oneToOneMetadata->associationMappings['user']['joinColumns'][0]['referencedColumnName']);


        $this->assertEquals('CMS_ADDRESS_CMS_USER', $manyToManyMetadata->associationMappings['user']['joinTable']['name']);

        $this->assertEquals(['CMS_ADDRESS_ID','CMS_USER_ID'], $manyToManyMetadata->associationMappings['user']['joinTableColumns']);
        $this->assertEquals(['CMS_ADDRESS_ID'=>'ID'], $manyToManyMetadata->associationMappings['user']['relationToSourceKeyColumns']);
        $this->assertEquals(['CMS_USER_ID'=>'ID'], $manyToManyMetadata->associationMappings['user']['relationToTargetKeyColumns']);

        $this->assertEquals('CMS_ADDRESS_ID', $manyToManyMetadata->associationMappings['user']['joinTable']['joinColumns'][0]['name']);
        $this->assertEquals('CMS_USER_ID', $manyToManyMetadata->associationMappings['user']['joinTable']['inverseJoinColumns'][0]['name']);

        $this->assertEquals('ID', $manyToManyMetadata->associationMappings['user']['joinTable']['joinColumns'][0]['referencedColumnName']);
        $this->assertEquals('ID', $manyToManyMetadata->associationMappings['user']['joinTable']['inverseJoinColumns'][0]['referencedColumnName']);


        $cm = new ClassMetadata('DoctrineGlobal_Article', $namingStrategy);
        $cm->mapManyToMany(['fieldName' => 'author', 'targetEntity' => CMS\CmsUser::class]);
        $this->assertEquals('DOCTRINE_GLOBAL_ARTICLE_CMS_USER', $cm->associationMappings['author']['joinTable']['name']);
    }

    /**
     * @group DDC-886
     */
    public function testSetMultipleIdentifierSetsComposite()
    {
        $cm = new ClassMetadata(CMS\CmsUser::class);
        $cm->initializeReflection(new RuntimeReflectionService());

        $cm->mapField(['fieldName' => 'name']);
        $cm->mapField(['fieldName' => 'username']);

        $cm->setIdentifier(['name', 'username']);
        $this->assertTrue($cm->isIdentifierComposite);
    }

    /**
     * @group DDC-944
     */
    public function testMappingNotFound()
    {
        $cm = new ClassMetadata(CMS\CmsUser::class);
        $cm->initializeReflection(new RuntimeReflectionService());

        $this->expectException(MappingException::class);
        $this->expectExceptionMessage("No mapping found for field 'foo' on class '" . CMS\CmsUser::class . "'.");

        $cm->getFieldMapping('foo');
    }

    /**
     * @group DDC-961
     */
    public function testJoinTableMappingDefaults()
    {
        $cm = new ClassMetadata('DoctrineGlobal_Article');
        $cm->initializeReflection(new RuntimeReflectionService());

        $cm->mapManyToMany(['fieldName' => 'author', 'targetEntity' => CMS\CmsUser::class]);

        $this->assertEquals('doctrineglobal_article_cmsuser', $cm->associationMappings['author']['joinTable']['name']);
    }

    /**
     * @group DDC-117
     */
    public function testMapIdentifierAssociation()
    {
        $cm = new ClassMetadata(DDC117ArticleDetails::class);
        $cm->initializeReflection(new RuntimeReflectionService());

        $cm->mapOneToOne(
            [
            'fieldName' => 'article',
            'id' => true,
            'targetEntity' => DDC117Article::class,
            'joinColumns' => [],
            ]
        );

        $this->assertTrue($cm->containsForeignIdentifier, "Identifier Association should set 'containsForeignIdentifier' boolean flag.");
        $this->assertEquals(["article"], $cm->identifier);
    }

    /**
     * @group DDC-117
     */
    public function testOrphanRemovalIdentifierAssociation()
    {
        $cm = new ClassMetadata(DDC117ArticleDetails::class);
        $cm->initializeReflection(new RuntimeReflectionService());

        $this->expectException(MappingException::class);
        $this->expectExceptionMessage('The orphan removal option is not allowed on an association that');

        $cm->mapOneToOne(
            [
            'fieldName' => 'article',
            'id' => true,
            'targetEntity' => DDC117Article::class,
            'orphanRemoval' => true,
            'joinColumns' => [],
            ]
        );
    }

    /**
     * @group DDC-117
     */
    public function testInverseIdentifierAssociation()
    {
        $cm = new ClassMetadata(DDC117ArticleDetails::class);
        $cm->initializeReflection(new RuntimeReflectionService());

        $this->expectException(MappingException::class);
        $this->expectExceptionMessage('An inverse association is not allowed to be identifier in');

        $cm->mapOneToOne(
            [
            'fieldName' => 'article',
            'id' => true,
            'mappedBy' => 'details', // INVERSE!
            'targetEntity' => DDC117Article::class,
            'joinColumns' => [],
            ]
        );
    }

    /**
     * @group DDC-117
     */
    public function testIdentifierAssociationManyToMany()
    {
        $cm = new ClassMetadata(DDC117ArticleDetails::class);
        $cm->initializeReflection(new RuntimeReflectionService());

        $this->expectException(MappingException::class);
        $this->expectExceptionMessage('Many-to-many or one-to-many associations are not allowed to be identifier in');

        $cm->mapManyToMany(
            [
            'fieldName' => 'article',
            'id' => true,
            'targetEntity' => DDC117Article::class,
            'joinColumns' => [],
            ]
        );
    }

    /**
     * @group DDC-996
     */
    public function testEmptyFieldNameThrowsException()
    {
        $this->expectException(MappingException::class);
        $this->expectExceptionMessage("The field or association mapping misses the 'fieldName' attribute in entity '" . CMS\CmsUser::class . "'.");

        $cm = new ClassMetadata(CMS\CmsUser::class);
        $cm->initializeReflection(new RuntimeReflectionService());

        $cm->mapField(['fieldName' => '']);
    }

    public function testRetrievalOfNamedQueries()
    {
        $cm = new ClassMetadata(CMS\CmsUser::class);
        $cm->initializeReflection(new RuntimeReflectionService());


        $this->assertEquals(0, count($cm->getNamedQueries()));

        $cm->addNamedQuery(
            [
            'name'  => 'userById',
            'query' => 'SELECT u FROM __CLASS__ u WHERE u.id = ?1'
            ]
        );

        $this->assertEquals(1, count($cm->getNamedQueries()));
    }

    /**
     * @group DDC-1663
     */
    public function testRetrievalOfResultSetMappings()
    {
        $cm = new ClassMetadata(CMS\CmsUser::class);
        $cm->initializeReflection(new RuntimeReflectionService());


        $this->assertEquals(0, count($cm->getSqlResultSetMappings()));

        $cm->addSqlResultSetMapping(
            [
            'name'      => 'find-all',
            'entities'  => [
                [
                    'entityClass'   => CMS\CmsUser::class,
                ],
            ],
            ]
        );

        $this->assertEquals(1, count($cm->getSqlResultSetMappings()));
    }

    public function testExistanceOfNamedQuery()
    {
        $cm = new ClassMetadata(CMS\CmsUser::class);
        $cm->initializeReflection(new RuntimeReflectionService());


        $cm->addNamedQuery(
            [
            'name'  => 'all',
            'query' => 'SELECT u FROM __CLASS__ u'
            ]
        );

        $this->assertTrue($cm->hasNamedQuery('all'));
        $this->assertFalse($cm->hasNamedQuery('userById'));
    }

    /**
     * @group DDC-1663
     */
    public function testRetrieveOfNamedNativeQuery()
    {
        $cm = new ClassMetadata(CMS\CmsUser::class);
        $cm->initializeReflection(new RuntimeReflectionService());

        $cm->addNamedNativeQuery(
            [
            'name'              => 'find-all',
            'query'             => 'SELECT * FROM cms_users',
            'resultSetMapping'  => 'result-mapping-name',
            'resultClass'       => CMS\CmsUser::class,
            ]
        );

        $cm->addNamedNativeQuery(
            [
            'name'              => 'find-by-id',
            'query'             => 'SELECT * FROM cms_users WHERE id = ?',
            'resultClass'       => '__CLASS__',
            'resultSetMapping'  => 'result-mapping-name',
            ]
        );

        $mapping = $cm->getNamedNativeQuery('find-all');
        $this->assertEquals('SELECT * FROM cms_users', $mapping['query']);
        $this->assertEquals('result-mapping-name', $mapping['resultSetMapping']);
        $this->assertEquals(CMS\CmsUser::class, $mapping['resultClass']);

        $mapping = $cm->getNamedNativeQuery('find-by-id');
        $this->assertEquals('SELECT * FROM cms_users WHERE id = ?', $mapping['query']);
        $this->assertEquals('result-mapping-name', $mapping['resultSetMapping']);
        $this->assertEquals(CMS\CmsUser::class, $mapping['resultClass']);
    }

    /**
     * @group DDC-1663
     */
    public function testRetrieveOfSqlResultSetMapping()
    {
        $cm = new ClassMetadata(CMS\CmsUser::class);
        $cm->initializeReflection(new RuntimeReflectionService());

        $cm->addSqlResultSetMapping(
            [
            'name'      => 'find-all',
            'entities'  => [
                [
                    'entityClass'   => '__CLASS__',
                    'fields'        => [
                        [
                            'name'  => 'id',
                            'column'=> 'id'
                        ],
                        [
                            'name'  => 'name',
                            'column'=> 'name'
                        ]
                    ]
                ],
                [
                    'entityClass'   => CMS\CmsEmail::class,
                    'fields'        => [
                        [
                            'name'  => 'id',
                            'column'=> 'id'
                        ],
                        [
                            'name'  => 'email',
                            'column'=> 'email'
                        ]
                    ]
                ]
            ],
            'columns'   => [
                [
                    'name' => 'scalarColumn'
                ]
            ]
            ]
        );

        $mapping = $cm->getSqlResultSetMapping('find-all');

        $this->assertEquals(CMS\CmsUser::class, $mapping['entities'][0]['entityClass']);
        $this->assertEquals(['name'=>'id','column'=>'id'], $mapping['entities'][0]['fields'][0]);
        $this->assertEquals(['name'=>'name','column'=>'name'], $mapping['entities'][0]['fields'][1]);

        $this->assertEquals(CMS\CmsEmail::class, $mapping['entities'][1]['entityClass']);
        $this->assertEquals(['name'=>'id','column'=>'id'], $mapping['entities'][1]['fields'][0]);
        $this->assertEquals(['name'=>'email','column'=>'email'], $mapping['entities'][1]['fields'][1]);

        $this->assertEquals('scalarColumn', $mapping['columns'][0]['name']);
    }

    /**
     * @group DDC-1663
     */
    public function testExistanceOfSqlResultSetMapping()
    {
        $cm = new ClassMetadata(CMS\CmsUser::class);
        $cm->initializeReflection(new RuntimeReflectionService());

        $cm->addSqlResultSetMapping(
            [
            'name'      => 'find-all',
            'entities'  => [
                [
                    'entityClass'   => CMS\CmsUser::class,
                ],
            ],
            ]
        );

        $this->assertTrue($cm->hasSqlResultSetMapping('find-all'));
        $this->assertFalse($cm->hasSqlResultSetMapping('find-by-id'));
    }

    /**
     * @group DDC-1663
     */
    public function testExistanceOfNamedNativeQuery()
    {
        $cm = new ClassMetadata(CMS\CmsUser::class);
        $cm->initializeReflection(new RuntimeReflectionService());


        $cm->addNamedNativeQuery(
            [
            'name'              => 'find-all',
            'query'             => 'SELECT * FROM cms_users',
            'resultClass'       => CMS\CmsUser::class,
            'resultSetMapping'  => 'result-mapping-name'
            ]
        );

        $this->assertTrue($cm->hasNamedNativeQuery('find-all'));
        $this->assertFalse($cm->hasNamedNativeQuery('find-by-id'));
    }

    public function testRetrieveOfNamedQuery()
    {
        $cm = new ClassMetadata(CMS\CmsUser::class);
        $cm->initializeReflection(new RuntimeReflectionService());


        $cm->addNamedQuery(
            [
            'name'  => 'userById',
            'query' => 'SELECT u FROM __CLASS__ u WHERE u.id = ?1'
            ]
        );

        $this->assertEquals('SELECT u FROM Doctrine\Tests\Models\CMS\CmsUser u WHERE u.id = ?1', $cm->getNamedQuery('userById'));
    }

    /**
     * @group DDC-1663
     */
    public function testRetrievalOfNamedNativeQueries()
    {
        $cm = new ClassMetadata(CMS\CmsUser::class);
        $cm->initializeReflection(new RuntimeReflectionService());

        $this->assertEquals(0, count($cm->getNamedNativeQueries()));

        $cm->addNamedNativeQuery(
            [
            'name'              => 'find-all',
            'query'             => 'SELECT * FROM cms_users',
            'resultClass'       => CMS\CmsUser::class,
            'resultSetMapping'  => 'result-mapping-name'
            ]
        );

        $this->assertEquals(1, count($cm->getNamedNativeQueries()));
    }

    /**
     * @group DDC-2451
     */
    public function testSerializeEntityListeners()
    {
        $metadata = new ClassMetadata(CompanyContract::class);

        $metadata->initializeReflection(new RuntimeReflectionService());
        $metadata->addEntityListener(Events::prePersist, 'CompanyContractListener', 'prePersistHandler');
        $metadata->addEntityListener(Events::postPersist, 'CompanyContractListener', 'postPersistHandler');

        $serialize   = serialize($metadata);
        $unserialize = unserialize($serialize);

        $this->assertEquals($metadata->entityListeners, $unserialize->entityListeners);
    }

    /**
     * @expectedException \Doctrine\ORM\Mapping\MappingException
     * @expectedExceptionMessage Query named "userById" in "Doctrine\Tests\Models\CMS\CmsUser" was already declared, but it must be declared only once
     */
    public function testNamingCollisionNamedQueryShouldThrowException()
    {
        $cm = new ClassMetadata(CMS\CmsUser::class);
        $cm->initializeReflection(new RuntimeReflectionService());

        $cm->addNamedQuery(
            [
            'name'  => 'userById',
            'query' => 'SELECT u FROM __CLASS__ u WHERE u.id = ?1'
            ]
        );

        $cm->addNamedQuery(
            [
            'name'  => 'userById',
            'query' => 'SELECT u FROM __CLASS__ u WHERE u.id = ?1'
            ]
        );
    }

    /**
     * @group DDC-1663
     *
     * @expectedException \Doctrine\ORM\Mapping\MappingException
     * @expectedExceptionMessage Query named "find-all" in "Doctrine\Tests\Models\CMS\CmsUser" was already declared, but it must be declared only once
     */
    public function testNamingCollisionNamedNativeQueryShouldThrowException()
    {
        $cm = new ClassMetadata(CMS\CmsUser::class);
        $cm->initializeReflection(new RuntimeReflectionService());

        $cm->addNamedNativeQuery(
            [
            'name'              => 'find-all',
            'query'             => 'SELECT * FROM cms_users',
            'resultClass'       => CMS\CmsUser::class,
            'resultSetMapping'  => 'result-mapping-name'
            ]
        );

        $cm->addNamedNativeQuery(
            [
            'name'              => 'find-all',
            'query'             => 'SELECT * FROM cms_users',
            'resultClass'       => CMS\CmsUser::class,
            'resultSetMapping'  => 'result-mapping-name'
            ]
        );
    }

    /**
     * @group DDC-1663
     *
     * @expectedException \Doctrine\ORM\Mapping\MappingException
     * @expectedExceptionMessage Result set mapping named "find-all" in "Doctrine\Tests\Models\CMS\CmsUser" was already declared, but it must be declared only once
     */
    public function testNamingCollisionSqlResultSetMappingShouldThrowException()
    {
        $cm = new ClassMetadata(CMS\CmsUser::class);
        $cm->initializeReflection(new RuntimeReflectionService());

        $cm->addSqlResultSetMapping(
            [
            'name'      => 'find-all',
            'entities'  => [
                [
                    'entityClass'   => CMS\CmsUser::class,
                ],
            ],
            ]
        );

        $cm->addSqlResultSetMapping(
            [
            'name'      => 'find-all',
            'entities'  => [
                [
                    'entityClass'   => CMS\CmsUser::class,
                ],
            ],
            ]
        );
    }

    /**
     * @group DDC-1068
     */
    public function testClassCaseSensitivity()
    {
        $user = new CMS\CmsUser();
        $cm = new ClassMetadata(strtoupper(CMS\CmsUser::class));
        $cm->initializeReflection(new RuntimeReflectionService());

        $this->assertEquals(CMS\CmsUser::class, $cm->name);
    }

    /**
     * @group DDC-659
     */
    public function testLifecycleCallbackNotFound()
    {
        $cm = new ClassMetadata(CMS\CmsUser::class);
        $cm->initializeReflection(new RuntimeReflectionService());
        $cm->addLifecycleCallback('notfound', 'postLoad');

        $this->expectException(MappingException::class);
        $this->expectExceptionMessage("Entity '" . CMS\CmsUser::class . "' has no method 'notfound' to be registered as lifecycle callback.");

        $cm->validateLifecycleCallbacks(new RuntimeReflectionService());
    }

    /**
     * @group ImproveErrorMessages
     */
    public function testTargetEntityNotFound()
    {
        $cm = new ClassMetadata(CMS\CmsUser::class);
        $cm->initializeReflection(new RuntimeReflectionService());
        $cm->mapManyToOne(['fieldName' => 'address', 'targetEntity' => 'UnknownClass']);

        $this->expectException(MappingException::class);
        $this->expectExceptionMessage("The target-entity Doctrine\\Tests\\Models\\CMS\\UnknownClass cannot be found in '" . CMS\CmsUser::class . "#address'.");

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
        $cm = new ClassMetadata(CMS\CmsUser::class);
        $cm->initializeReflection(new RuntimeReflectionService());
        $cm->addNamedQuery(
            [
            'query' => 'SELECT u FROM Doctrine\Tests\Models\CMS\CmsUser u',
            ]
        );
    }

    /**
     * @group DDC-1663
     *
     * @expectedException \Doctrine\ORM\Mapping\MappingException
     * @expectedExceptionMessage Query name on entity class 'Doctrine\Tests\Models\CMS\CmsUser' is not defined.
     */
    public function testNameIsMandatoryForNameNativeQueryMappingException()
    {
        $cm = new ClassMetadata(CMS\CmsUser::class);
        $cm->initializeReflection(new RuntimeReflectionService());
        $cm->addNamedQuery(
            [
            'query'             => 'SELECT * FROM cms_users',
            'resultClass'       => CMS\CmsUser::class,
            'resultSetMapping'  => 'result-mapping-name'
            ]
        );
    }

    /**
     * @group DDC-1663
     *
     * @expectedException \Doctrine\ORM\Mapping\MappingException
     * @expectedExceptionMessage Result set mapping named "find-all" in "Doctrine\Tests\Models\CMS\CmsUser requires a entity class name.
     */
    public function testNameIsMandatoryForEntityNameSqlResultSetMappingException()
    {
        $cm = new ClassMetadata(CMS\CmsUser::class);
        $cm->initializeReflection(new RuntimeReflectionService());
        $cm->addSqlResultSetMapping(
            [
            'name'      => 'find-all',
            'entities'  => [
                [
                    'fields' => []
                ]
            ],
            ]
        );
    }

    /**
     * @expectedException \Doctrine\ORM\Mapping\MappingException
     * @expectedExceptionMessage Discriminator column name on entity class 'Doctrine\Tests\Models\CMS\CmsUser' is not defined.
     */
    public function testNameIsMandatoryForDiscriminatorColumnsMappingException()
    {
        $cm = new ClassMetadata(CMS\CmsUser::class);
        $cm->initializeReflection(new RuntimeReflectionService());
        $cm->setDiscriminatorColumn([]);
    }

    /**
     * @group DDC-984
     * @group DDC-559
     * @group DDC-1575
     */
    public function testFullyQualifiedClassNameShouldBeGivenToNamingStrategy()
    {
        $namingStrategy     = new MyNamespacedNamingStrategy();
        $addressMetadata    = new ClassMetadata(CMS\CmsAddress::class, $namingStrategy);
        $articleMetadata    = new ClassMetadata(DoctrineGlobal_Article::class, $namingStrategy);
        $routingMetadata    = new ClassMetadata(RoutingLeg::class, $namingStrategy);

        $addressMetadata->initializeReflection(new RuntimeReflectionService());
        $articleMetadata->initializeReflection(new RuntimeReflectionService());
        $routingMetadata->initializeReflection(new RuntimeReflectionService());

        $addressMetadata->mapManyToMany(
            [
            'fieldName'     => 'user',
            'targetEntity'  => 'CmsUser'
            ]
        );

        $articleMetadata->mapManyToMany(
            [
            'fieldName'     => 'author',
            'targetEntity'  => CMS\CmsUser::class
            ]
        );

        $this->assertEquals('routing_routingleg', $routingMetadata->table['name']);
        $this->assertEquals('cms_cmsaddress_cms_cmsuser', $addressMetadata->associationMappings['user']['joinTable']['name']);
        $this->assertEquals('doctrineglobal_article_cms_cmsuser', $articleMetadata->associationMappings['author']['joinTable']['name']);
    }

    /**
     * @group DDC-984
     * @group DDC-559
     */
    public function testFullyQualifiedClassNameShouldBeGivenToNamingStrategyPropertyToColumnName()
    {
        $namingStrategy = new MyPrefixNamingStrategy();
        $metadata       = new ClassMetadata(CMS\CmsAddress::class, $namingStrategy);

        $metadata->initializeReflection(new RuntimeReflectionService());

        $metadata->mapField(['fieldName'=>'country']);
        $metadata->mapField(['fieldName'=>'city']);

        $this->assertEquals($metadata->fieldNames, [
            'cmsaddress_country'   => 'country',
            'cmsaddress_city'      => 'city'
        ]
        );
    }

    /**
     * @group DDC-1746
     */
    public function testInvalidCascade()
    {
        $cm = new ClassMetadata(CMS\CmsUser::class);
        $cm->initializeReflection(new RuntimeReflectionService());

        $this->expectException(MappingException::class);
        $this->expectExceptionMessage("You have specified invalid cascade options for " . CMS\CmsUser::class . "::\$address: 'invalid'; available options: 'remove', 'persist', 'refresh', 'merge', and 'detach'");

        $cm->mapManyToOne(['fieldName' => 'address', 'targetEntity' => 'UnknownClass', 'cascade' => ['invalid']]);
     }

    /**
     * @group DDC-964
     * @expectedException        Doctrine\ORM\Mapping\MappingException
     * @expectedExceptionMessage Invalid field override named 'invalidPropertyName' for class 'Doctrine\Tests\Models\DDC964\DDC964Admin
     */
    public function testInvalidPropertyAssociationOverrideNameException()
    {
        $cm = new ClassMetadata(DDC964Admin::class);
        $cm->initializeReflection(new RuntimeReflectionService());
        $cm->mapManyToOne(['fieldName' => 'address', 'targetEntity' => 'DDC964Address']);

        $cm->setAssociationOverride('invalidPropertyName', []);
    }

    /**
     * @group DDC-964
     * @expectedException        Doctrine\ORM\Mapping\MappingException
     * @expectedExceptionMessage Invalid field override named 'invalidPropertyName' for class 'Doctrine\Tests\Models\DDC964\DDC964Guest'.
     */
    public function testInvalidPropertyAttributeOverrideNameException()
    {
        $cm = new ClassMetadata(DDC964Guest::class);
        $cm->initializeReflection(new RuntimeReflectionService());
        $cm->mapField(['fieldName' => 'name']);

        $cm->setAttributeOverride('invalidPropertyName', []);
    }

    /**
     * @group DDC-964
     * @expectedException        Doctrine\ORM\Mapping\MappingException
     * @expectedExceptionMessage The column type of attribute 'name' on class 'Doctrine\Tests\Models\DDC964\DDC964Guest' could not be changed.
     */
    public function testInvalidOverrideAttributeFieldTypeException()
    {
        $cm = new ClassMetadata(DDC964Guest::class);
        $cm->initializeReflection(new RuntimeReflectionService());
        $cm->mapField(['fieldName' => 'name', 'type'=>'string']);

        $cm->setAttributeOverride('name', ['type'=>'date']);
    }

    /**
     * @group DDC-1955
     *
     * @expectedException        Doctrine\ORM\Mapping\MappingException
     * @expectedExceptionMessage Entity Listener "\InvalidClassName" declared on "Doctrine\Tests\Models\CMS\CmsUser" not found.
     */
    public function testInvalidEntityListenerClassException()
    {
        $cm = new ClassMetadata(CMS\CmsUser::class);
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
        $cm = new ClassMetadata(CMS\CmsUser::class);
        $cm->initializeReflection(new RuntimeReflectionService());

        $cm->addEntityListener(Events::postLoad, '\Doctrine\Tests\Models\Company\CompanyContractListener', 'invalidMethod');
    }

    public function testManyToManySelfReferencingNamingStrategyDefaults()
    {
        $cm = new ClassMetadata(CustomTypeParent::class);
        $cm->initializeReflection(new RuntimeReflectionService());
        $cm->mapManyToMany(
            [
                'fieldName' => 'friendsWithMe',
                'targetEntity' => 'CustomTypeParent'
            ]
        );

        $this->assertEquals(
            [
                'name' => 'customtypeparent_customtypeparent',
                'joinColumns' => [['name' => 'customtypeparent_source', 'referencedColumnName' => 'id', 'onDelete' => 'CASCADE']],
                'inverseJoinColumns' => [['name' => 'customtypeparent_target', 'referencedColumnName' => 'id', 'onDelete' => 'CASCADE']],
            ],
            $cm->associationMappings['friendsWithMe']['joinTable']
        );
        $this->assertEquals(['customtypeparent_source', 'customtypeparent_target'], $cm->associationMappings['friendsWithMe']['joinTableColumns']);
        $this->assertEquals(['customtypeparent_source' => 'id'], $cm->associationMappings['friendsWithMe']['relationToSourceKeyColumns']);
        $this->assertEquals(['customtypeparent_target' => 'id'], $cm->associationMappings['friendsWithMe']['relationToTargetKeyColumns']);
    }

    /**
     * @group DDC-2608
     */
    public function testSetSequenceGeneratorThrowsExceptionWhenSequenceNameIsMissing()
    {
        $cm = new ClassMetadata(CMS\CmsUser::class);
        $cm->initializeReflection(new RuntimeReflectionService());

        $this->expectException(\Doctrine\ORM\Mapping\MappingException::class);
        $cm->setSequenceGeneratorDefinition([]);
    }

    /**
     * @group DDC-2662
     * @group 6682
     */
    public function testQuotedSequenceName() : void
    {
        $cm = new ClassMetadata(CMS\CmsUser::class);

        $cm->initializeReflection(new RuntimeReflectionService());
        $cm->setSequenceGeneratorDefinition(['sequenceName' => '`foo`']);

        self::assertSame(
            ['sequenceName' => 'foo', 'quoted' => true, 'allocationSize' => '1', 'initialValue' => '1'],
            $cm->sequenceGeneratorDefinition
        );
    }

    /**
     * @group DDC-2700
     */
    public function testIsIdentifierMappedSuperClass()
    {
        $class = new ClassMetadata(DDC2700MappedSuperClass::class);

        $this->assertFalse($class->isIdentifier('foo'));
    }

    /**
     * @group DDC-3120
     */
    public function testCanInstantiateInternalPhpClassSubclass()
    {
        $classMetadata = new ClassMetadata(MyArrayObjectEntity::class);

        $this->assertInstanceOf(MyArrayObjectEntity::class, $classMetadata->newInstance());
    }

    /**
     * @group DDC-3120
     */
    public function testCanInstantiateInternalPhpClassSubclassFromUnserializedMetadata()
    {
        /* @var $classMetadata ClassMetadata */
        $classMetadata = unserialize(serialize(new ClassMetadata(MyArrayObjectEntity::class)));

        $classMetadata->wakeupReflection(new RuntimeReflectionService());

        $this->assertInstanceOf(MyArrayObjectEntity::class, $classMetadata->newInstance());
    }

    public function testWakeupReflectionWithEmbeddableAndStaticReflectionService()
    {
        $classMetadata = new ClassMetadata(TestEntity1::class);

        $classMetadata->mapEmbedded(
            [
            'fieldName'    => 'test',
            'class'        => TestEntity1::class,
            'columnPrefix' => false,
            ]
        );

        $field = [
            'fieldName' => 'test.embeddedProperty',
            'type' => 'string',
            'originalClass' => TestEntity1::class,
            'declaredField' => 'test',
            'originalField' => 'embeddedProperty'
        ];

        $classMetadata->mapField($field);
        $classMetadata->wakeupReflection(new StaticReflectionService());

        $this->assertEquals(['test' => null, 'test.embeddedProperty' => null], $classMetadata->getReflectionProperties());
    }

    public function testGetColumnNamesWithGivenFieldNames()
    {
        $metadata = new ClassMetadata(CMS\CmsUser::class);
        $metadata->initializeReflection(new RuntimeReflectionService());

        $metadata->mapField(['fieldName' => 'status', 'type' => 'string', 'columnName' => 'foo']);
        $metadata->mapField(['fieldName' => 'username', 'type' => 'string', 'columnName' => 'bar']);
        $metadata->mapField(['fieldName' => 'name', 'type' => 'string', 'columnName' => 'baz']);

        self::assertSame(['foo', 'baz'], $metadata->getColumnNames(['status', 'name']));
    }

    /**
     * @group DDC-6460
     */
    public function testInlineEmbeddable()
    {
        $classMetadata = new ClassMetadata(TestEntity1::class);

        $classMetadata->mapEmbedded(
            [
                'fieldName'    => 'test',
                'class'        => TestEntity1::class,
                'columnPrefix' => false,
            ]
        );

        $this->assertTrue($classMetadata->hasField('test'));
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
