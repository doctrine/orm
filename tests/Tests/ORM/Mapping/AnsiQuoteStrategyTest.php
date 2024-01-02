<?php

declare(strict_types=1);

namespace Doctrine\Tests\ORM\Mapping;

use Doctrine\DBAL\Platforms\AbstractPlatform;
use Doctrine\ORM\Mapping\AnsiQuoteStrategy;
use Doctrine\ORM\Mapping\ClassMetadata;
use Doctrine\Persistence\Mapping\RuntimeReflectionService;
use Doctrine\Tests\Models\CMS\CmsAddress;
use Doctrine\Tests\Models\CMS\CmsUser;
use Doctrine\Tests\Models\DDC117\DDC117Article;
use Doctrine\Tests\Models\DDC117\DDC117ArticleDetails;
use Doctrine\Tests\OrmTestCase;

/**
 * @group DDC-1845
 * @group DDC-2459
 */
class AnsiQuoteStrategyTest extends OrmTestCase
{
    /** @var AnsiQuoteStrategy */
    private $strategy;

    /** @var AbstractPlatform */
    private $platform;

    protected function setUp(): void
    {
        parent::setUp();

        $em             = $this->getTestEntityManager();
        $this->platform = $em->getConnection()->getDatabasePlatform();
        $this->strategy = new AnsiQuoteStrategy();
    }

    private function createClassMetadata(string $className): ClassMetadata
    {
        $class = new ClassMetadata($className);
        $class->initializeReflection(new RuntimeReflectionService());

        return $class;
    }

    public function testGetColumnName(): void
    {
        $class = $this->createClassMetadata(CmsUser::class);
        $class->mapField(['fieldName' => 'name', 'columnName' => 'name']);
        $class->mapField(['fieldName' => 'id', 'columnName' => 'id', 'id' => true]);

        self::assertEquals('id', $this->strategy->getColumnName('id', $class, $this->platform));
        self::assertEquals('name', $this->strategy->getColumnName('name', $class, $this->platform));
    }

    public function testGetTableName(): void
    {
        $class = $this->createClassMetadata(CmsUser::class);

        $class->setPrimaryTable(['name' => 'cms_user']);
        self::assertEquals('cms_user', $this->strategy->getTableName($class, $this->platform));
    }

    public function testJoinTableName(): void
    {
        $class = $this->createClassMetadata(CmsAddress::class);

        $class->mapManyToMany(
            [
                'fieldName'     => 'user',
                'targetEntity'  => 'CmsUser',
                'inversedBy'    => 'users',
                'joinTable'     => ['name' => 'cmsaddress_cmsuser'],
            ]
        );

        self::assertEquals('cmsaddress_cmsuser', $this->strategy->getJoinTableName($class->associationMappings['user'], $class, $this->platform));
    }

    public function testIdentifierColumnNames(): void
    {
        $class = $this->createClassMetadata(CmsAddress::class);

        $class->mapField(
            [
                'id'            => true,
                'fieldName'     => 'id',
                'columnName'    => 'id',
            ]
        );

        self::assertEquals(['id'], $this->strategy->getIdentifierColumnNames($class, $this->platform));
    }

    public function testColumnAlias(): void
    {
        self::assertEquals('columnName_1', $this->strategy->getColumnAlias('columnName', 1, $this->platform));
    }

    public function testJoinColumnName(): void
    {
        $class = $this->createClassMetadata(DDC117ArticleDetails::class);

        $class->mapOneToOne(
            [
                'id'            => true,
                'fieldName'     => 'article',
                'targetEntity'  => DDC117Article::class,
                'joinColumns'    => [
                    ['name' => 'article'],
                ],
            ]
        );

        $joinColumn = $class->associationMappings['article']['joinColumns'][0];
        self::assertEquals('article', $this->strategy->getJoinColumnName($joinColumn, $class, $this->platform));
    }

    public function testReferencedJoinColumnName(): void
    {
        $cm = $this->createClassMetadata(DDC117ArticleDetails::class);

        $cm->mapOneToOne(
            [
                'id'            => true,
                'fieldName'     => 'article',
                'targetEntity'  => DDC117Article::class,
                'joinColumns'    => [
                    ['name' => 'article'],
                ],
            ]
        );

        $joinColumn = $cm->associationMappings['article']['joinColumns'][0];
        self::assertEquals('id', $this->strategy->getReferencedJoinColumnName($joinColumn, $cm, $this->platform));
    }

    public function testGetSequenceName(): void
    {
        $class      = $this->createClassMetadata(CmsUser::class);
        $definition = [
            'sequenceName'      => 'user_id_seq',
            'allocationSize'    => 1,
            'initialValue'      => 2,
        ];

        $class->setSequenceGeneratorDefinition($definition);

        self::assertEquals('user_id_seq', $this->strategy->getSequenceName($definition, $class, $this->platform));
    }
}
