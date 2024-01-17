<?php

declare(strict_types=1);

namespace Doctrine\Tests\ORM\Mapping;

use ArrayObject;
use Doctrine\DBAL\Types\Types;
use Doctrine\ORM\Events;
use Doctrine\ORM\Mapping\ChainTypedFieldMapper;
use Doctrine\ORM\Mapping\ClassMetadata;
use Doctrine\ORM\Mapping\Column;
use Doctrine\ORM\Mapping\DefaultNamingStrategy;
use Doctrine\ORM\Mapping\DefaultTypedFieldMapper;
use Doctrine\ORM\Mapping\MappedSuperclass;
use Doctrine\ORM\Mapping\MappingException;
use Doctrine\ORM\Mapping\UnderscoreNamingStrategy;
use Doctrine\Persistence\Mapping\RuntimeReflectionService;
use Doctrine\Persistence\Mapping\StaticReflectionService;
use Doctrine\Tests\DbalTypes\CustomIdObject;
use Doctrine\Tests\DbalTypes\CustomIdObjectType;
use Doctrine\Tests\DbalTypes\CustomIntType;
use Doctrine\Tests\Models\CMS;
use Doctrine\Tests\Models\CMS\CmsEmail;
use Doctrine\Tests\Models\Company\CompanyContract;
use Doctrine\Tests\Models\CustomType\CustomTypeParent;
use Doctrine\Tests\Models\DDC117\DDC117Article;
use Doctrine\Tests\Models\DDC117\DDC117ArticleDetails;
use Doctrine\Tests\Models\DDC6412\DDC6412File;
use Doctrine\Tests\Models\DDC964\DDC964Admin;
use Doctrine\Tests\Models\DDC964\DDC964Guest;
use Doctrine\Tests\Models\DirectoryTree\AbstractContentItem;
use Doctrine\Tests\Models\DirectoryTree\Directory;
use Doctrine\Tests\Models\Routing\RoutingLeg;
use Doctrine\Tests\Models\TypedProperties;
use Doctrine\Tests\ORM\Mapping\TypedFieldMapper\CustomIntAsStringTypedFieldMapper;
use Doctrine\Tests\OrmTestCase;
use DoctrineGlobalArticle;
use ReflectionClass;

use function assert;
use function count;
use function serialize;
use function str_contains;
use function str_replace;
use function strtolower;
use function strtoupper;
use function unserialize;

use const CASE_UPPER;
use const PHP_VERSION_ID;

require_once __DIR__ . '/../../Models/Global/GlobalNamespaceModel.php';

class ClassMetadataTest extends OrmTestCase
{
    public function testClassMetadataInstanceSerialization(): void
    {
        $cm = new ClassMetadata(CMS\CmsUser::class);
        $cm->initializeReflection(new RuntimeReflectionService());

        // Test initial state
        self::assertTrue(count($cm->getReflectionProperties()) === 0);
        self::assertInstanceOf('ReflectionClass', $cm->reflClass);
        self::assertEquals(CMS\CmsUser::class, $cm->name);
        self::assertEquals(CMS\CmsUser::class, $cm->rootEntityName);
        self::assertEquals([], $cm->subClasses);
        self::assertEquals([], $cm->parentClasses);
        self::assertEquals(ClassMetadata::INHERITANCE_TYPE_NONE, $cm->inheritanceType);

        // Customize state
        $cm->setInheritanceType(ClassMetadata::INHERITANCE_TYPE_SINGLE_TABLE);
        $cm->setSubclasses(['One', 'Two', 'Three']);
        $cm->setParentClasses(['UserParent']);
        $cm->setCustomRepositoryClass('UserRepository');
        $cm->setDiscriminatorColumn(['name' => 'disc', 'type' => 'integer']);
        $cm->mapOneToOne(['fieldName' => 'phonenumbers', 'targetEntity' => 'CmsAddress', 'mappedBy' => 'foo']);
        $cm->markReadOnly();
        $cm->mapField(['fieldName' => 'status', 'notInsertable' => true, 'generated' => ClassMetadata::GENERATED_ALWAYS]);
        $cm->addNamedQuery(['name' => 'dql', 'query' => 'foo']);

        self::assertTrue($cm->requiresFetchAfterChange);
        self::assertEquals(1, count($cm->associationMappings));

        $serialized = serialize($cm);
        $cm         = unserialize($serialized);
        $cm->wakeupReflection(new RuntimeReflectionService());

        // Check state
        self::assertTrue(count($cm->getReflectionProperties()) > 0);
        self::assertEquals('Doctrine\Tests\Models\CMS', $cm->namespace);
        self::assertInstanceOf(ReflectionClass::class, $cm->reflClass);
        self::assertEquals(CMS\CmsUser::class, $cm->name);
        self::assertEquals('UserParent', $cm->rootEntityName);
        self::assertEquals([CMS\One::class, CMS\Two::class, CMS\Three::class], $cm->subClasses);
        self::assertEquals(['UserParent'], $cm->parentClasses);
        self::assertEquals(CMS\UserRepository::class, $cm->customRepositoryClassName);
        self::assertEquals(['name' => 'disc', 'type' => 'integer', 'fieldName' => 'disc'], $cm->discriminatorColumn);
        self::assertTrue($cm->associationMappings['phonenumbers']['type'] === ClassMetadata::ONE_TO_ONE);
        self::assertEquals(1, count($cm->associationMappings));
        $oneOneMapping = $cm->getAssociationMapping('phonenumbers');
        self::assertTrue($oneOneMapping['fetch'] === ClassMetadata::FETCH_LAZY);
        self::assertEquals('phonenumbers', $oneOneMapping['fieldName']);
        self::assertEquals(CMS\CmsAddress::class, $oneOneMapping['targetEntity']);
        self::assertTrue($cm->isReadOnly);
        self::assertEquals(['dql' => ['name' => 'dql', 'query' => 'foo', 'dql' => 'foo']], $cm->namedQueries);
        self::assertTrue($cm->requiresFetchAfterChange);
    }

    public function testFieldIsNullable(): void
    {
        $cm = new ClassMetadata(CMS\CmsUser::class);
        $cm->initializeReflection(new RuntimeReflectionService());

        // Explicit Nullable
        $cm->mapField(['fieldName' => 'status', 'nullable' => true, 'type' => 'string', 'length' => 50]);
        self::assertTrue($cm->isNullable('status'));

        // Explicit Not Nullable
        $cm->mapField(['fieldName' => 'username', 'nullable' => false, 'type' => 'string', 'length' => 50]);
        self::assertFalse($cm->isNullable('username'));

        // Implicit Not Nullable
        $cm->mapField(['fieldName' => 'name', 'type' => 'string', 'length' => 50]);
        self::assertFalse($cm->isNullable('name'), 'By default a field should not be nullable.');
    }

    public function testFieldIsNullableByType(): void
    {
        if (PHP_VERSION_ID < 70400) {
            self::markTestSkipped('requies PHP 7.4');
        }

        $cm = new ClassMetadata(TypedProperties\UserTyped::class);
        $cm->initializeReflection(new RuntimeReflectionService());

        $cm->mapOneToOne(['fieldName' => 'email', 'joinColumns' => [[]]]);
        self::assertEquals(CmsEmail::class, $cm->getAssociationMapping('email')['targetEntity']);

        $cm->mapManyToOne(['fieldName' => 'mainEmail']);
        self::assertEquals(CmsEmail::class, $cm->getAssociationMapping('mainEmail')['targetEntity']);

        $cm->mapEmbedded(['fieldName' => 'contact']);
        self::assertEquals(TypedProperties\Contact::class, $cm->embeddedClasses['contact']['class']);
    }

    public function testFieldTypeFromReflection(): void
    {
        if (PHP_VERSION_ID < 70400) {
            self::markTestSkipped('requies PHP 7.4');
        }

        $cm = new ClassMetadata(TypedProperties\UserTyped::class);
        $cm->initializeReflection(new RuntimeReflectionService());

        // Integer
        $cm->mapField(['fieldName' => 'id']);
        self::assertEquals('integer', $cm->getTypeOfField('id'));

        // String
        $cm->mapField(['fieldName' => 'username', 'length' => 50]);
        self::assertEquals('string', $cm->getTypeOfField('username'));

        // DateInterval object
        $cm->mapField(['fieldName' => 'dateInterval']);
        self::assertEquals('dateinterval', $cm->getTypeOfField('dateInterval'));

        // DateTime object
        $cm->mapField(['fieldName' => 'dateTime']);
        self::assertEquals('datetime', $cm->getTypeOfField('dateTime'));

        // DateTimeImmutable object
        $cm->mapField(['fieldName' => 'dateTimeImmutable']);
        self::assertEquals('datetime_immutable', $cm->getTypeOfField('dateTimeImmutable'));

        // array as JSON
        $cm->mapField(['fieldName' => 'array']);
        self::assertEquals('json', $cm->getTypeOfField('array'));

        // bool
        $cm->mapField(['fieldName' => 'boolean']);
        self::assertEquals('boolean', $cm->getTypeOfField('boolean'));

        // float
        $cm->mapField(['fieldName' => 'float']);
        self::assertEquals('float', $cm->getTypeOfField('float'));
    }

    /**
     * @group GH10313
     * @requires PHP 7.4
     */
    public function testFieldTypeFromReflectionDefaultTypedFieldMapper(): void
    {
        $cm = new ClassMetadata(
            TypedProperties\UserTypedWithCustomTypedField::class,
            null,
            new DefaultTypedFieldMapper(
                [
                    CustomIdObject::class => CustomIdObjectType::class,
                    'int' => CustomIntType::class,
                ]
            )
        );
        $cm->initializeReflection(new RuntimeReflectionService());

        $cm->mapField(['fieldName' => 'customId']);
        $cm->mapField(['fieldName' => 'customIntTypedField']);
        self::assertEquals(CustomIdObjectType::class, $cm->getTypeOfField('customId'));
        self::assertEquals(CustomIntType::class, $cm->getTypeOfField('customIntTypedField'));
    }

    /**
     * @group GH10313
     * @requires PHP 7.4
     */
    public function testFieldTypeFromReflectionChainTypedFieldMapper(): void
    {
        $cm = new ClassMetadata(
            TypedProperties\UserTyped::class,
            null,
            new ChainTypedFieldMapper(
                new CustomIntAsStringTypedFieldMapper(),
                new DefaultTypedFieldMapper()
            )
        );
        $cm->initializeReflection(new RuntimeReflectionService());

        $cm->mapField(['fieldName' => 'id']);
        $cm->mapField(['fieldName' => 'username']);
        self::assertEquals(Types::STRING, $cm->getTypeOfField('id'));
        self::assertEquals(Types::STRING, $cm->getTypeOfField('username'));
    }

    /** @group DDC-115 */
    public function testMapAssociationInGlobalNamespace(): void
    {
        require_once __DIR__ . '/../../Models/Global/GlobalNamespaceModel.php';

        $cm = new ClassMetadata('DoctrineGlobalArticle');
        $cm->initializeReflection(new RuntimeReflectionService());
        $cm->mapManyToMany(
            [
                'fieldName' => 'author',
                'targetEntity' => 'DoctrineGlobalUser',
                'joinTable' => [
                    'name' => 'bar',
                    'joinColumns' => [['name' => 'bar_id', 'referencedColumnName' => 'id']],
                    'inverseJoinColumns' => [['name' => 'baz_id', 'referencedColumnName' => 'id']],
                ],
            ]
        );

        self::assertEquals('DoctrineGlobalUser', $cm->associationMappings['author']['targetEntity']);
    }

    public function testMapManyToManyJoinTableDefaults(): void
    {
        $cm = new ClassMetadata(CMS\CmsUser::class);
        $cm->initializeReflection(new RuntimeReflectionService());
        $cm->mapManyToMany(
            [
                'fieldName' => 'groups',
                'targetEntity' => 'CmsGroup',
            ]
        );

        $assoc = $cm->associationMappings['groups'];
        self::assertEquals(
            [
                'name' => 'cmsuser_cmsgroup',
                'joinColumns' => [['name' => 'cmsuser_id', 'referencedColumnName' => 'id', 'onDelete' => 'CASCADE']],
                'inverseJoinColumns' => [['name' => 'cmsgroup_id', 'referencedColumnName' => 'id', 'onDelete' => 'CASCADE']],
            ],
            $assoc['joinTable']
        );
        self::assertTrue($assoc['isOnDeleteCascade']);
    }

    public function testSerializeManyToManyJoinTableCascade(): void
    {
        $cm = new ClassMetadata(CMS\CmsUser::class);
        $cm->initializeReflection(new RuntimeReflectionService());
        $cm->mapManyToMany(
            [
                'fieldName' => 'groups',
                'targetEntity' => 'CmsGroup',
            ]
        );

        $assoc = $cm->associationMappings['groups'];
        $assoc = unserialize(serialize($assoc));

        self::assertTrue($assoc['isOnDeleteCascade']);
    }

    /** @group DDC-115 */
    public function testSetDiscriminatorMapInGlobalNamespace(): void
    {
        require_once __DIR__ . '/../../Models/Global/GlobalNamespaceModel.php';

        $cm = new ClassMetadata('DoctrineGlobalUser');
        $cm->initializeReflection(new RuntimeReflectionService());
        $cm->setDiscriminatorMap(['descr' => 'DoctrineGlobalArticle', 'foo' => 'DoctrineGlobalUser']);

        self::assertEquals('DoctrineGlobalArticle', $cm->discriminatorMap['descr']);
        self::assertEquals('DoctrineGlobalUser', $cm->discriminatorMap['foo']);
    }

    /** @group DDC-115 */
    public function testSetSubClassesInGlobalNamespace(): void
    {
        require_once __DIR__ . '/../../Models/Global/GlobalNamespaceModel.php';

        $cm = new ClassMetadata('DoctrineGlobalUser');
        $cm->initializeReflection(new RuntimeReflectionService());
        $cm->setSubclasses(['DoctrineGlobalArticle']);

        self::assertEquals('DoctrineGlobalArticle', $cm->subClasses[0]);
    }

    /** @group DDC-268 */
    public function testSetInvalidVersionMappingThrowsException(): void
    {
        $field              = [];
        $field['fieldName'] = 'foo';
        $field['type']      = 'string';

        $cm = new ClassMetadata(CMS\CmsUser::class);
        $cm->initializeReflection(new RuntimeReflectionService());

        $this->expectException(MappingException::class);
        $cm->setVersionMapping($field);
    }

    public function testGetSingleIdentifierFieldNameMultipleIdentifierEntityThrowsException(): void
    {
        $cm = new ClassMetadata(CMS\CmsUser::class);
        $cm->initializeReflection(new RuntimeReflectionService());
        $cm->isIdentifierComposite = true;

        $this->expectException(MappingException::class);
        $cm->getSingleIdentifierFieldName();
    }

    public function testGetSingleIdentifierFieldNameNoIdEntityThrowsException(): void
    {
        $cm = new ClassMetadata(DDC6412File::class);
        $cm->initializeReflection(new RuntimeReflectionService());

        $this->expectException(MappingException::class);
        $cm->getSingleIdentifierFieldName();
    }

    public function testDuplicateAssociationMappingException(): void
    {
        $cm = new ClassMetadata(CMS\CmsUser::class);
        $cm->initializeReflection(new RuntimeReflectionService());

        $a1 = ['fieldName' => 'foo', 'sourceEntity' => 'stdClass', 'targetEntity' => 'stdClass', 'mappedBy' => 'foo'];
        $a2 = ['fieldName' => 'foo', 'sourceEntity' => 'stdClass', 'targetEntity' => 'stdClass', 'mappedBy' => 'foo'];

        $cm->addInheritedAssociationMapping($a1);
        $this->expectException(MappingException::class);
        $cm->addInheritedAssociationMapping($a2);
    }

    public function testDuplicateColumnNameThrowsMappingException(): void
    {
        $cm = new ClassMetadata(CMS\CmsUser::class);
        $cm->initializeReflection(new RuntimeReflectionService());

        $cm->mapField(['fieldName' => 'name', 'columnName' => 'name']);

        $this->expectException(MappingException::class);
        $cm->mapField(['fieldName' => 'username', 'columnName' => 'name']);
    }

    public function testDuplicateColumnNameDiscriminatorColumnThrowsMappingException(): void
    {
        $cm = new ClassMetadata(CMS\CmsUser::class);
        $cm->initializeReflection(new RuntimeReflectionService());

        $cm->mapField(['fieldName' => 'name', 'columnName' => 'name']);

        $this->expectException(MappingException::class);
        $cm->setDiscriminatorColumn(['name' => 'name']);
    }

    public function testDuplicateColumnNameDiscriminatorColumn2ThrowsMappingException(): void
    {
        $cm = new ClassMetadata(CMS\CmsUser::class);
        $cm->initializeReflection(new RuntimeReflectionService());

        $cm->setDiscriminatorColumn(['name' => 'name']);

        $this->expectException(MappingException::class);
        $cm->mapField(['fieldName' => 'name', 'columnName' => 'name']);
    }

    public function testDuplicateFieldAndAssociationMapping1ThrowsException(): void
    {
        $cm = new ClassMetadata(CMS\CmsUser::class);
        $cm->initializeReflection(new RuntimeReflectionService());

        $cm->mapField(['fieldName' => 'name', 'columnName' => 'name']);

        $this->expectException(MappingException::class);
        $cm->mapOneToOne(['fieldName' => 'name', 'targetEntity' => 'CmsUser']);
    }

    public function testDuplicateFieldAndAssociationMapping2ThrowsException(): void
    {
        $cm = new ClassMetadata(CMS\CmsUser::class);
        $cm->initializeReflection(new RuntimeReflectionService());

        $cm->mapOneToOne(['fieldName' => 'name', 'targetEntity' => 'CmsUser']);

        $this->expectException(MappingException::class);
        $cm->mapField(['fieldName' => 'name', 'columnName' => 'name']);
    }

    /** @group DDC-1224 */
    public function testGetTemporaryTableNameSchema(): void
    {
        $cm = new ClassMetadata(CMS\CmsUser::class);
        $cm->initializeReflection(new RuntimeReflectionService());

        $cm->setTableName('foo.bar');

        self::assertEquals('foo_bar_id_tmp', $cm->getTemporaryIdTableName());
    }

    public function testDefaultTableName(): void
    {
        $cm = new ClassMetadata(CMS\CmsUser::class);
        $cm->initializeReflection(new RuntimeReflectionService());

        // When table's name is not given
        $primaryTable = [];
        $cm->setPrimaryTable($primaryTable);

        self::assertEquals('CmsUser', $cm->getTableName());
        self::assertEquals('CmsUser', $cm->table['name']);

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
                    'inverseJoinColumns' => [['referencedColumnName' => 'id']],
                ],
            ]
        );
        self::assertEquals('cmsaddress_cmsuser', $cm->associationMappings['user']['joinTable']['name']);
    }

    public function testDefaultJoinColumnName(): void
    {
        $cm = new ClassMetadata(CMS\CmsAddress::class);
        $cm->initializeReflection(new RuntimeReflectionService());

        // this is really dirty, but it's the simplest way to test whether
        // joinColumn's name will be automatically set to user_id
        $cm->mapOneToOne(
            [
                'fieldName' => 'user',
                'targetEntity' => 'CmsUser',
                'joinColumns' => [['referencedColumnName' => 'id']],
            ]
        );
        self::assertEquals('user_id', $cm->associationMappings['user']['joinColumns'][0]['name']);

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
                    'inverseJoinColumns' => [['referencedColumnName' => 'id']],
                ],
            ]
        );
        self::assertEquals('cmsaddress_id', $cm->associationMappings['user']['joinTable']['joinColumns'][0]['name']);
        self::assertEquals('cmsuser_id', $cm->associationMappings['user']['joinTable']['inverseJoinColumns'][0]['name']);
    }

    /** @group DDC-559 */
    public function testUnderscoreNamingStrategyDefaults(): void
    {
        $namingStrategy     = new UnderscoreNamingStrategy(CASE_UPPER);
        $oneToOneMetadata   = new ClassMetadata(CMS\CmsAddress::class, $namingStrategy);
        $manyToManyMetadata = new ClassMetadata(CMS\CmsAddress::class, $namingStrategy);

        $oneToOneMetadata->mapOneToOne(
            [
                'fieldName'     => 'user',
                'targetEntity'  => 'CmsUser',
            ]
        );

        $manyToManyMetadata->mapManyToMany(
            [
                'fieldName'     => 'user',
                'targetEntity'  => 'CmsUser',
            ]
        );

        self::assertEquals(['USER_ID' => 'ID'], $oneToOneMetadata->associationMappings['user']['sourceToTargetKeyColumns']);
        self::assertEquals(['USER_ID' => 'USER_ID'], $oneToOneMetadata->associationMappings['user']['joinColumnFieldNames']);
        self::assertEquals(['ID' => 'USER_ID'], $oneToOneMetadata->associationMappings['user']['targetToSourceKeyColumns']);

        self::assertEquals('USER_ID', $oneToOneMetadata->associationMappings['user']['joinColumns'][0]['name']);
        self::assertEquals('ID', $oneToOneMetadata->associationMappings['user']['joinColumns'][0]['referencedColumnName']);

        self::assertEquals('CMS_ADDRESS_CMS_USER', $manyToManyMetadata->associationMappings['user']['joinTable']['name']);

        self::assertEquals(['CMS_ADDRESS_ID', 'CMS_USER_ID'], $manyToManyMetadata->associationMappings['user']['joinTableColumns']);
        self::assertEquals(['CMS_ADDRESS_ID' => 'ID'], $manyToManyMetadata->associationMappings['user']['relationToSourceKeyColumns']);
        self::assertEquals(['CMS_USER_ID' => 'ID'], $manyToManyMetadata->associationMappings['user']['relationToTargetKeyColumns']);

        self::assertEquals('CMS_ADDRESS_ID', $manyToManyMetadata->associationMappings['user']['joinTable']['joinColumns'][0]['name']);
        self::assertEquals('CMS_USER_ID', $manyToManyMetadata->associationMappings['user']['joinTable']['inverseJoinColumns'][0]['name']);

        self::assertEquals('ID', $manyToManyMetadata->associationMappings['user']['joinTable']['joinColumns'][0]['referencedColumnName']);
        self::assertEquals('ID', $manyToManyMetadata->associationMappings['user']['joinTable']['inverseJoinColumns'][0]['referencedColumnName']);

        $cm = new ClassMetadata('DoctrineGlobalArticle', $namingStrategy);
        $cm->mapManyToMany(['fieldName' => 'author', 'targetEntity' => CMS\CmsUser::class]);
        self::assertEquals('DOCTRINE_GLOBAL_ARTICLE_CMS_USER', $cm->associationMappings['author']['joinTable']['name']);
    }

    /** @group DDC-886 */
    public function testSetMultipleIdentifierSetsComposite(): void
    {
        $cm = new ClassMetadata(CMS\CmsUser::class);
        $cm->initializeReflection(new RuntimeReflectionService());

        $cm->mapField(['fieldName' => 'name']);
        $cm->mapField(['fieldName' => 'username']);

        $cm->setIdentifier(['name', 'username']);
        self::assertTrue($cm->isIdentifierComposite);
    }

    /** @group DDC-944 */
    public function testMappingNotFound(): void
    {
        $cm = new ClassMetadata(CMS\CmsUser::class);
        $cm->initializeReflection(new RuntimeReflectionService());

        $this->expectException(MappingException::class);
        $this->expectExceptionMessage("No mapping found for field 'foo' on class '" . CMS\CmsUser::class . "'.");

        $cm->getFieldMapping('foo');
    }

    /** @group DDC-961 */
    public function testJoinTableMappingDefaults(): void
    {
        $cm = new ClassMetadata('DoctrineGlobalArticle');
        $cm->initializeReflection(new RuntimeReflectionService());

        $cm->mapManyToMany(['fieldName' => 'author', 'targetEntity' => CMS\CmsUser::class]);

        self::assertEquals('doctrineglobalarticle_cmsuser', $cm->associationMappings['author']['joinTable']['name']);
    }

    /** @group DDC-117 */
    public function testMapIdentifierAssociation(): void
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

        self::assertTrue($cm->containsForeignIdentifier, "Identifier Association should set 'containsForeignIdentifier' boolean flag.");
        self::assertEquals(['article'], $cm->identifier);
    }

    /** @group DDC-117 */
    public function testOrphanRemovalIdentifierAssociation(): void
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

    /** @group DDC-117 */
    public function testInverseIdentifierAssociation(): void
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

    /** @group DDC-117 */
    public function testIdentifierAssociationManyToMany(): void
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

    /** @group DDC-996 */
    public function testEmptyFieldNameThrowsException(): void
    {
        $this->expectException(MappingException::class);
        $this->expectExceptionMessage("The field or association mapping misses the 'fieldName' attribute in entity '" . CMS\CmsUser::class . "'.");

        $cm = new ClassMetadata(CMS\CmsUser::class);
        $cm->initializeReflection(new RuntimeReflectionService());

        $cm->mapField(['fieldName' => '']);
    }

    public function testRetrievalOfNamedQueries(): void
    {
        $cm = new ClassMetadata(CMS\CmsUser::class);
        $cm->initializeReflection(new RuntimeReflectionService());

        self::assertCount(0, $cm->getNamedQueries());

        $cm->addNamedQuery(
            [
                'name'  => 'userById',
                'query' => 'SELECT u FROM __CLASS__ u WHERE u.id = ?1',
            ]
        );

        self::assertCount(1, $cm->getNamedQueries());
    }

    /** @group DDC-1663 */
    public function testRetrievalOfResultSetMappings(): void
    {
        $cm = new ClassMetadata(CMS\CmsUser::class);
        $cm->initializeReflection(new RuntimeReflectionService());

        self::assertCount(0, $cm->getSqlResultSetMappings());

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

        self::assertCount(1, $cm->getSqlResultSetMappings());
    }

    public function testExistanceOfNamedQuery(): void
    {
        $cm = new ClassMetadata(CMS\CmsUser::class);
        $cm->initializeReflection(new RuntimeReflectionService());

        $cm->addNamedQuery(
            [
                'name'  => 'all',
                'query' => 'SELECT u FROM __CLASS__ u',
            ]
        );

        self::assertTrue($cm->hasNamedQuery('all'));
        self::assertFalse($cm->hasNamedQuery('userById'));
    }

    /** @group DDC-1663 */
    public function testRetrieveOfNamedNativeQuery(): void
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
        self::assertEquals('SELECT * FROM cms_users', $mapping['query']);
        self::assertEquals('result-mapping-name', $mapping['resultSetMapping']);
        self::assertEquals(CMS\CmsUser::class, $mapping['resultClass']);

        $mapping = $cm->getNamedNativeQuery('find-by-id');
        self::assertEquals('SELECT * FROM cms_users WHERE id = ?', $mapping['query']);
        self::assertEquals('result-mapping-name', $mapping['resultSetMapping']);
        self::assertEquals(CMS\CmsUser::class, $mapping['resultClass']);
    }

    /** @group DDC-1663 */
    public function testRetrieveOfSqlResultSetMapping(): void
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
                                'column' => 'id',
                            ],
                            [
                                'name'  => 'name',
                                'column' => 'name',
                            ],
                        ],
                    ],
                    [
                        'entityClass'   => CMS\CmsEmail::class,
                        'fields'        => [
                            [
                                'name'  => 'id',
                                'column' => 'id',
                            ],
                            [
                                'name'  => 'email',
                                'column' => 'email',
                            ],
                        ],
                    ],
                ],
                'columns'   => [
                    ['name' => 'scalarColumn'],
                ],
            ]
        );

        $mapping = $cm->getSqlResultSetMapping('find-all');

        self::assertEquals(CMS\CmsUser::class, $mapping['entities'][0]['entityClass']);
        self::assertEquals(['name' => 'id', 'column' => 'id'], $mapping['entities'][0]['fields'][0]);
        self::assertEquals(['name' => 'name', 'column' => 'name'], $mapping['entities'][0]['fields'][1]);

        self::assertEquals(CMS\CmsEmail::class, $mapping['entities'][1]['entityClass']);
        self::assertEquals(['name' => 'id', 'column' => 'id'], $mapping['entities'][1]['fields'][0]);
        self::assertEquals(['name' => 'email', 'column' => 'email'], $mapping['entities'][1]['fields'][1]);

        self::assertEquals('scalarColumn', $mapping['columns'][0]['name']);
    }

    /** @group DDC-1663 */
    public function testExistanceOfSqlResultSetMapping(): void
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

        self::assertTrue($cm->hasSqlResultSetMapping('find-all'));
        self::assertFalse($cm->hasSqlResultSetMapping('find-by-id'));
    }

    /** @group DDC-1663 */
    public function testExistanceOfNamedNativeQuery(): void
    {
        $cm = new ClassMetadata(CMS\CmsUser::class);
        $cm->initializeReflection(new RuntimeReflectionService());

        $cm->addNamedNativeQuery(
            [
                'name'              => 'find-all',
                'query'             => 'SELECT * FROM cms_users',
                'resultClass'       => CMS\CmsUser::class,
                'resultSetMapping'  => 'result-mapping-name',
            ]
        );

        self::assertTrue($cm->hasNamedNativeQuery('find-all'));
        self::assertFalse($cm->hasNamedNativeQuery('find-by-id'));
    }

    public function testRetrieveOfNamedQuery(): void
    {
        $cm = new ClassMetadata(CMS\CmsUser::class);
        $cm->initializeReflection(new RuntimeReflectionService());

        $cm->addNamedQuery(
            [
                'name'  => 'userById',
                'query' => 'SELECT u FROM __CLASS__ u WHERE u.id = ?1',
            ]
        );

        self::assertEquals('SELECT u FROM Doctrine\Tests\Models\CMS\CmsUser u WHERE u.id = ?1', $cm->getNamedQuery('userById'));
    }

    /** @group DDC-1663 */
    public function testRetrievalOfNamedNativeQueries(): void
    {
        $cm = new ClassMetadata(CMS\CmsUser::class);
        $cm->initializeReflection(new RuntimeReflectionService());

        self::assertCount(0, $cm->getNamedNativeQueries());

        $cm->addNamedNativeQuery(
            [
                'name'              => 'find-all',
                'query'             => 'SELECT * FROM cms_users',
                'resultClass'       => CMS\CmsUser::class,
                'resultSetMapping'  => 'result-mapping-name',
            ]
        );

        self::assertCount(1, $cm->getNamedNativeQueries());
    }

    /** @group DDC-2451 */
    public function testSerializeEntityListeners(): void
    {
        $metadata = new ClassMetadata(CompanyContract::class);

        $metadata->initializeReflection(new RuntimeReflectionService());
        $metadata->addEntityListener(Events::prePersist, 'CompanyContractListener', 'prePersistHandler');
        $metadata->addEntityListener(Events::postPersist, 'CompanyContractListener', 'postPersistHandler');

        $serialize   = serialize($metadata);
        $unserialize = unserialize($serialize);

        self::assertEquals($metadata->entityListeners, $unserialize->entityListeners);
    }

    public function testNamingCollisionNamedQueryShouldThrowException(): void
    {
        $this->expectException('Doctrine\ORM\Mapping\MappingException');
        $this->expectExceptionMessage('Query named "userById" in "Doctrine\Tests\Models\CMS\CmsUser" was already declared, but it must be declared only once');
        $cm = new ClassMetadata(CMS\CmsUser::class);
        $cm->initializeReflection(new RuntimeReflectionService());

        $cm->addNamedQuery(
            [
                'name'  => 'userById',
                'query' => 'SELECT u FROM __CLASS__ u WHERE u.id = ?1',
            ]
        );

        $cm->addNamedQuery(
            [
                'name'  => 'userById',
                'query' => 'SELECT u FROM __CLASS__ u WHERE u.id = ?1',
            ]
        );
    }

    /** @group DDC-1663 */
    public function testNamingCollisionNamedNativeQueryShouldThrowException(): void
    {
        $this->expectException('Doctrine\ORM\Mapping\MappingException');
        $this->expectExceptionMessage('Query named "find-all" in "Doctrine\Tests\Models\CMS\CmsUser" was already declared, but it must be declared only once');
        $cm = new ClassMetadata(CMS\CmsUser::class);
        $cm->initializeReflection(new RuntimeReflectionService());

        $cm->addNamedNativeQuery(
            [
                'name'              => 'find-all',
                'query'             => 'SELECT * FROM cms_users',
                'resultClass'       => CMS\CmsUser::class,
                'resultSetMapping'  => 'result-mapping-name',
            ]
        );

        $cm->addNamedNativeQuery(
            [
                'name'              => 'find-all',
                'query'             => 'SELECT * FROM cms_users',
                'resultClass'       => CMS\CmsUser::class,
                'resultSetMapping'  => 'result-mapping-name',
            ]
        );
    }

    /** @group DDC-1663 */
    public function testNamingCollisionSqlResultSetMappingShouldThrowException(): void
    {
        $this->expectException('Doctrine\ORM\Mapping\MappingException');
        $this->expectExceptionMessage('Result set mapping named "find-all" in "Doctrine\Tests\Models\CMS\CmsUser" was already declared, but it must be declared only once');
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

    /** @group DDC-1068 */
    public function testClassCaseSensitivity(): void
    {
        $user = new CMS\CmsUser();
        $cm   = new ClassMetadata(strtoupper(CMS\CmsUser::class));
        $cm->initializeReflection(new RuntimeReflectionService());

        self::assertEquals(CMS\CmsUser::class, $cm->name);
    }

    /** @group DDC-659 */
    public function testLifecycleCallbackNotFound(): void
    {
        $cm = new ClassMetadata(CMS\CmsUser::class);
        $cm->initializeReflection(new RuntimeReflectionService());
        $cm->addLifecycleCallback('notfound', 'postLoad');

        $this->expectException(MappingException::class);
        $this->expectExceptionMessage("Entity '" . CMS\CmsUser::class . "' has no method 'notfound' to be registered as lifecycle callback.");

        $cm->validateLifecycleCallbacks(new RuntimeReflectionService());
    }

    /** @group ImproveErrorMessages */
    public function testTargetEntityNotFound(): void
    {
        $cm = new ClassMetadata(CMS\CmsUser::class);
        $cm->initializeReflection(new RuntimeReflectionService());
        $cm->mapManyToOne(['fieldName' => 'address', 'targetEntity' => 'UnknownClass']);

        $this->expectException(MappingException::class);
        $this->expectExceptionMessage("The target-entity Doctrine\\Tests\\Models\\CMS\\UnknownClass cannot be found in '" . CMS\CmsUser::class . "#address'.");

        $cm->validateAssociations();
    }

    /** @group DDC-1663 */
    public function testNameIsMandatoryForNamedQueryMappingException(): void
    {
        $this->expectException('Doctrine\ORM\Mapping\MappingException');
        $this->expectExceptionMessage('Query name on entity class \'Doctrine\Tests\Models\CMS\CmsUser\' is not defined.');
        $cm = new ClassMetadata(CMS\CmsUser::class);
        $cm->initializeReflection(new RuntimeReflectionService());
        $cm->addNamedQuery(
            ['query' => 'SELECT u FROM Doctrine\Tests\Models\CMS\CmsUser u']
        );
    }

    /** @group DDC-1663 */
    public function testNameIsMandatoryForNameNativeQueryMappingException(): void
    {
        $this->expectException('Doctrine\ORM\Mapping\MappingException');
        $this->expectExceptionMessage('Query name on entity class \'Doctrine\Tests\Models\CMS\CmsUser\' is not defined.');
        $cm = new ClassMetadata(CMS\CmsUser::class);
        $cm->initializeReflection(new RuntimeReflectionService());
        $cm->addNamedQuery(
            [
                'query'             => 'SELECT * FROM cms_users',
                'resultClass'       => CMS\CmsUser::class,
                'resultSetMapping'  => 'result-mapping-name',
            ]
        );
    }

    /** @group DDC-1663 */
    public function testNameIsMandatoryForEntityNameSqlResultSetMappingException(): void
    {
        $this->expectException('Doctrine\ORM\Mapping\MappingException');
        $this->expectExceptionMessage('Result set mapping named "find-all" in "Doctrine\Tests\Models\CMS\CmsUser requires a entity class name.');
        $cm = new ClassMetadata(CMS\CmsUser::class);
        $cm->initializeReflection(new RuntimeReflectionService());
        $cm->addSqlResultSetMapping(
            [
                'name'      => 'find-all',
                'entities'  => [
                    [
                        'fields' => [],
                    ],
                ],
            ]
        );
    }

    public function testNameIsMandatoryForDiscriminatorColumnsMappingException(): void
    {
        $this->expectException('Doctrine\ORM\Mapping\MappingException');
        $this->expectExceptionMessage('Discriminator column name on entity class \'Doctrine\Tests\Models\CMS\CmsUser\' is not defined.');
        $cm = new ClassMetadata(CMS\CmsUser::class);
        $cm->initializeReflection(new RuntimeReflectionService());
        $cm->setDiscriminatorColumn([]);
    }

    /**
     * @group DDC-984
     * @group DDC-559
     * @group DDC-1575
     */
    public function testFullyQualifiedClassNameShouldBeGivenToNamingStrategy(): void
    {
        $namingStrategy  = new MyNamespacedNamingStrategy();
        $addressMetadata = new ClassMetadata(CMS\CmsAddress::class, $namingStrategy);
        $articleMetadata = new ClassMetadata(DoctrineGlobalArticle::class, $namingStrategy);
        $routingMetadata = new ClassMetadata(RoutingLeg::class, $namingStrategy);

        $addressMetadata->initializeReflection(new RuntimeReflectionService());
        $articleMetadata->initializeReflection(new RuntimeReflectionService());
        $routingMetadata->initializeReflection(new RuntimeReflectionService());

        $addressMetadata->mapManyToMany(
            [
                'fieldName'     => 'user',
                'targetEntity'  => 'CmsUser',
            ]
        );

        $articleMetadata->mapManyToMany(
            [
                'fieldName'     => 'author',
                'targetEntity'  => CMS\CmsUser::class,
            ]
        );

        self::assertEquals('routing_routingleg', $routingMetadata->table['name']);
        self::assertEquals('cms_cmsaddress_cms_cmsuser', $addressMetadata->associationMappings['user']['joinTable']['name']);
        self::assertEquals('doctrineglobalarticle_cms_cmsuser', $articleMetadata->associationMappings['author']['joinTable']['name']);
    }

    /**
     * @group DDC-984
     * @group DDC-559
     */
    public function testFullyQualifiedClassNameShouldBeGivenToNamingStrategyPropertyToColumnName(): void
    {
        $namingStrategy = new MyPrefixNamingStrategy();
        $metadata       = new ClassMetadata(CMS\CmsAddress::class, $namingStrategy);

        $metadata->initializeReflection(new RuntimeReflectionService());

        $metadata->mapField(['fieldName' => 'country']);
        $metadata->mapField(['fieldName' => 'city']);

        self::assertEquals($metadata->fieldNames, [
            'cmsaddress_country'   => 'country',
            'cmsaddress_city'      => 'city',
        ]);
    }

    /** @group DDC-1746 */
    public function testInvalidCascade(): void
    {
        $cm = new ClassMetadata(CMS\CmsUser::class);
        $cm->initializeReflection(new RuntimeReflectionService());

        $this->expectException(MappingException::class);
        $this->expectExceptionMessage('You have specified invalid cascade options for ' . CMS\CmsUser::class . "::\$address: 'invalid'; available options: 'remove', 'persist', 'refresh', 'merge', and 'detach'");

        $cm->mapManyToOne(['fieldName' => 'address', 'targetEntity' => 'UnknownClass', 'cascade' => ['invalid']]);
    }

    /** @group DDC-964 */
    public function testInvalidPropertyAssociationOverrideNameException(): void
    {
        if (PHP_VERSION_ID >= 80000 && PHP_VERSION_ID < 80100) {
            $this->markTestSkipped('Does not work on PHP 8.0.* due to nested attributes missing.');
        }

        $this->expectException('Doctrine\ORM\Mapping\MappingException');
        $this->expectExceptionMessage('Invalid field override named \'invalidPropertyName\' for class \'Doctrine\Tests\Models\DDC964\DDC964Admin');
        $cm = new ClassMetadata(DDC964Admin::class);
        $cm->initializeReflection(new RuntimeReflectionService());
        $cm->mapManyToOne(['fieldName' => 'address', 'targetEntity' => 'DDC964Address']);

        $cm->setAssociationOverride('invalidPropertyName', []);
    }

    /** @group DDC-964 */
    public function testInvalidPropertyAttributeOverrideNameException(): void
    {
        if (PHP_VERSION_ID >= 80000 && PHP_VERSION_ID < 80100) {
            $this->markTestSkipped('Does not work on PHP 8.0.* due to nested attributes missing.');
        }

        $this->expectException('Doctrine\ORM\Mapping\MappingException');
        $this->expectExceptionMessage('Invalid field override named \'invalidPropertyName\' for class \'Doctrine\Tests\Models\DDC964\DDC964Guest\'.');
        $cm = new ClassMetadata(DDC964Guest::class);
        $cm->initializeReflection(new RuntimeReflectionService());
        $cm->mapField(['fieldName' => 'name']);

        $cm->setAttributeOverride('invalidPropertyName', []);
    }

    /** @group DDC-964 */
    public function testInvalidOverrideAttributeFieldTypeException(): void
    {
        if (PHP_VERSION_ID >= 80000 && PHP_VERSION_ID < 80100) {
            $this->markTestSkipped('Does not work on PHP 8.0.* due to nested attributes missing.');
        }

        $this->expectException('Doctrine\ORM\Mapping\MappingException');
        $this->expectExceptionMessage('The column type of attribute \'name\' on class \'Doctrine\Tests\Models\DDC964\DDC964Guest\' could not be changed.');
        $cm = new ClassMetadata(DDC964Guest::class);
        $cm->initializeReflection(new RuntimeReflectionService());
        $cm->mapField(['fieldName' => 'name', 'type' => 'string']);

        $cm->setAttributeOverride('name', ['type' => 'date']);
    }

    public function testAttributeOverrideKeepsDeclaringClass(): void
    {
        $cm = new ClassMetadata(Directory::class);
        $cm->mapField(['fieldName' => 'id', 'type' => 'integer', 'declared' => AbstractContentItem::class]);
        $cm->setAttributeOverride('id', ['columnName' => 'new_id']);

        $mapping = $cm->getFieldMapping('id');

        self::assertArrayHasKey('declared', $mapping);
        self::assertSame(AbstractContentItem::class, $mapping['declared']);
    }

    public function testAssociationOverrideKeepsDeclaringClass(): void
    {
        $cm = new ClassMetadata(Directory::class);
        $cm->mapManyToOne(['fieldName' => 'parentDirectory', 'targetEntity' => Directory::class, 'cascade' => ['remove'], 'declared' => Directory::class]);
        $cm->setAssociationOverride('parentDirectory', ['cascade' => '']);

        $mapping = $cm->getAssociationMapping('parentDirectory');

        self::assertArrayHasKey('declared', $mapping);
        self::assertSame(Directory::class, $mapping['declared']);
    }

    /** @group DDC-1955 */
    public function testInvalidEntityListenerClassException(): void
    {
        $this->expectException('Doctrine\ORM\Mapping\MappingException');
        $this->expectExceptionMessage('Entity Listener "\InvalidClassName" declared on "Doctrine\Tests\Models\CMS\CmsUser" not found.');
        $cm = new ClassMetadata(CMS\CmsUser::class);
        $cm->initializeReflection(new RuntimeReflectionService());

        $cm->addEntityListener(Events::postLoad, '\InvalidClassName', 'postLoadHandler');
    }

    /** @group DDC-1955 */
    public function testInvalidEntityListenerMethodException(): void
    {
        $this->expectException('Doctrine\ORM\Mapping\MappingException');
        $this->expectExceptionMessage('Entity Listener "\Doctrine\Tests\Models\Company\CompanyContractListener" declared on "Doctrine\Tests\Models\CMS\CmsUser" has no method "invalidMethod".');
        $cm = new ClassMetadata(CMS\CmsUser::class);
        $cm->initializeReflection(new RuntimeReflectionService());

        $cm->addEntityListener(Events::postLoad, '\Doctrine\Tests\Models\Company\CompanyContractListener', 'invalidMethod');
    }

    public function testManyToManySelfReferencingNamingStrategyDefaults(): void
    {
        $cm = new ClassMetadata(CustomTypeParent::class);
        $cm->initializeReflection(new RuntimeReflectionService());
        $cm->mapManyToMany(
            [
                'fieldName' => 'friendsWithMe',
                'targetEntity' => 'CustomTypeParent',
            ]
        );

        self::assertEquals(
            [
                'name' => 'customtypeparent_customtypeparent',
                'joinColumns' => [['name' => 'customtypeparent_source', 'referencedColumnName' => 'id', 'onDelete' => 'CASCADE']],
                'inverseJoinColumns' => [['name' => 'customtypeparent_target', 'referencedColumnName' => 'id', 'onDelete' => 'CASCADE']],
            ],
            $cm->associationMappings['friendsWithMe']['joinTable']
        );
        self::assertEquals(['customtypeparent_source', 'customtypeparent_target'], $cm->associationMappings['friendsWithMe']['joinTableColumns']);
        self::assertEquals(['customtypeparent_source' => 'id'], $cm->associationMappings['friendsWithMe']['relationToSourceKeyColumns']);
        self::assertEquals(['customtypeparent_target' => 'id'], $cm->associationMappings['friendsWithMe']['relationToTargetKeyColumns']);
    }

    /** @group DDC-2608 */
    public function testSetSequenceGeneratorThrowsExceptionWhenSequenceNameIsMissing(): void
    {
        $cm = new ClassMetadata(CMS\CmsUser::class);
        $cm->initializeReflection(new RuntimeReflectionService());

        $this->expectException(MappingException::class);
        $cm->setSequenceGeneratorDefinition([]);
    }

    /**
     * @group DDC-2662
     * @group 6682
     */
    public function testQuotedSequenceName(): void
    {
        $cm = new ClassMetadata(CMS\CmsUser::class);

        $cm->initializeReflection(new RuntimeReflectionService());
        $cm->setSequenceGeneratorDefinition(['sequenceName' => '`foo`']);

        self::assertSame(
            ['sequenceName' => 'foo', 'quoted' => true, 'allocationSize' => '1', 'initialValue' => '1'],
            $cm->sequenceGeneratorDefinition
        );
    }

    /** @group DDC-2700 */
    public function testIsIdentifierMappedSuperClass(): void
    {
        $class = new ClassMetadata(DDC2700MappedSuperClass::class);

        self::assertFalse($class->isIdentifier('foo'));
    }

    /** @group DDC-3120 */
    public function testCanInstantiateInternalPhpClassSubclass(): void
    {
        $classMetadata = new ClassMetadata(MyArrayObjectEntity::class);

        self::assertInstanceOf(MyArrayObjectEntity::class, $classMetadata->newInstance());
    }

    /** @group DDC-3120 */
    public function testCanInstantiateInternalPhpClassSubclassFromUnserializedMetadata(): void
    {
        $classMetadata = unserialize(serialize(new ClassMetadata(MyArrayObjectEntity::class)));
        assert($classMetadata instanceof ClassMetadata);

        $classMetadata->wakeupReflection(new RuntimeReflectionService());

        self::assertInstanceOf(MyArrayObjectEntity::class, $classMetadata->newInstance());
    }

    public function testWakeupReflectionWithEmbeddableAndStaticReflectionService(): void
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
            'originalField' => 'embeddedProperty',
        ];

        $classMetadata->mapField($field);
        $classMetadata->wakeupReflection(new StaticReflectionService());

        self::assertEquals(['test' => null, 'test.embeddedProperty' => null], $classMetadata->getReflectionProperties());
    }

    public function testGetColumnNamesWithGivenFieldNames(): void
    {
        $metadata = new ClassMetadata(CMS\CmsUser::class);
        $metadata->initializeReflection(new RuntimeReflectionService());

        $metadata->mapField(['fieldName' => 'status', 'type' => 'string', 'columnName' => 'foo']);
        $metadata->mapField(['fieldName' => 'username', 'type' => 'string', 'columnName' => 'bar']);
        $metadata->mapField(['fieldName' => 'name', 'type' => 'string', 'columnName' => 'baz']);

        self::assertSame(['foo', 'baz'], $metadata->getColumnNames(['status', 'name']));
    }

    /** @group DDC-6460 */
    public function testInlineEmbeddable(): void
    {
        $classMetadata = new ClassMetadata(TestEntity1::class);

        $classMetadata->mapEmbedded(
            [
                'fieldName'    => 'test',
                'class'        => TestEntity1::class,
                'columnPrefix' => false,
            ]
        );

        self::assertTrue($classMetadata->hasField('test'));
    }

    /** @group DDC-3305 */
    public function testRejectsEmbeddableWithoutValidClassName(): void
    {
        $metadata = new ClassMetadata(TestEntity1::class);

        $this->expectException(MappingException::class);
        $this->expectExceptionMessage('The embed mapping \'embedded\' misses the \'class\' attribute.');
        $metadata->mapEmbedded([
            'fieldName'    => 'embedded',
            'class'        => '',
            'columnPrefix' => false,
        ]);
    }
}

/** @MappedSuperclass */
class DDC2700MappedSuperClass
{
    /**
     * @var mixed
     * @Column
     */
    private $foo;
}

class MyNamespacedNamingStrategy extends DefaultNamingStrategy
{
    /**
     * {@inheritDoc}
     */
    public function classToTableName($className)
    {
        if (str_contains($className, '\\')) {
            $className = str_replace('\\', '_', str_replace('Doctrine\Tests\Models\\', '', $className));
        }

        return strtolower($className);
    }
}

class MyPrefixNamingStrategy extends DefaultNamingStrategy
{
    /**
     * {@inheritDoc}
     */
    public function propertyToColumnName($propertyName, $className = null)
    {
        return strtolower($this->classToTableName($className)) . '_' . $propertyName;
    }
}

class MyArrayObjectEntity extends ArrayObject
{
}
