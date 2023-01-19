<?php

declare(strict_types=1);

namespace Doctrine\Tests\ORM\Mapping;

use Doctrine\DBAL\Platforms\AbstractPlatform;
use Doctrine\ORM\Mapping\ClassMetadata;
use Doctrine\ORM\Mapping\DefaultQuoteStrategy;
use Doctrine\ORM\Mapping\QuoteStrategy;
use Doctrine\Persistence\Mapping\RuntimeReflectionService;
use Doctrine\Tests\Models\CMS\CmsAddress;
use Doctrine\Tests\Models\CMS\CmsUser;
use Doctrine\Tests\Models\DDC117\DDC117Article;
use Doctrine\Tests\Models\DDC117\DDC117ArticleDetails;
use Doctrine\Tests\OrmTestCase;

/** @group DDC-1845 */
class QuoteStrategyTest extends OrmTestCase
{
    /** @var DefaultQuoteStrategy */
    private $strategy;

    /** @var AbstractPlatform */
    private $platform;

    protected function setUp(): void
    {
        parent::setUp();

        $em             = $this->getTestEntityManager();
        $this->platform = $em->getConnection()->getDatabasePlatform();
        $this->strategy = new DefaultQuoteStrategy();
    }

    private function createClassMetadata(string $className): ClassMetadata
    {
        $cm = new ClassMetadata($className);
        $cm->initializeReflection(new RuntimeReflectionService());

        return $cm;
    }

    public function testConfiguration(): void
    {
        $em     = $this->getTestEntityManager();
        $config = $em->getConfiguration();

        self::assertInstanceOf(QuoteStrategy::class, $config->getQuoteStrategy());
        self::assertInstanceOf(DefaultQuoteStrategy::class, $config->getQuoteStrategy());

        $config->setQuoteStrategy(new MyQuoteStrategy());

        self::assertInstanceOf(QuoteStrategy::class, $config->getQuoteStrategy());
        self::assertInstanceOf(MyQuoteStrategy::class, $config->getQuoteStrategy());
    }

    public function testGetColumnName(): void
    {
        $cm = $this->createClassMetadata(CmsUser::class);
        $cm->mapField(['fieldName' => 'name', 'columnName' => '`name`']);
        $cm->mapField(['fieldName' => 'id', 'columnName' => 'id']);

        self::assertEquals('id', $this->strategy->getColumnName('id', $cm, $this->platform));
        self::assertEquals('"name"', $this->strategy->getColumnName('name', $cm, $this->platform));
    }

    public function testGetTableName(): void
    {
        $cm = $this->createClassMetadata(CmsUser::class);
        $cm->setPrimaryTable(['name' => '`cms_user`']);
        self::assertEquals('"cms_user"', $this->strategy->getTableName($cm, $this->platform));

        $cm = new ClassMetadata(CmsUser::class);
        $cm->initializeReflection(new RuntimeReflectionService());
        $cm->setPrimaryTable(['name' => 'cms_user']);
        self::assertEquals('cms_user', $this->strategy->getTableName($cm, $this->platform));
    }

    public function testJoinTableName(): void
    {
        $cm1 = $this->createClassMetadata(CmsAddress::class);
        $cm2 = $this->createClassMetadata(CmsAddress::class);

        $cm1->mapManyToMany(
            [
                'fieldName'     => 'user',
                'targetEntity'  => 'CmsUser',
                'inversedBy'    => 'users',
                'joinTable'     => ['name' => '`cmsaddress_cmsuser`'],
            ]
        );

        $cm2->mapManyToMany(
            [
                'fieldName'     => 'user',
                'targetEntity'  => 'CmsUser',
                'inversedBy'    => 'users',
                'joinTable'     => ['name' => 'cmsaddress_cmsuser'],
            ]
        );

        self::assertEquals('"cmsaddress_cmsuser"', $this->strategy->getJoinTableName($cm1->associationMappings['user'], $cm1, $this->platform));
        self::assertEquals('cmsaddress_cmsuser', $this->strategy->getJoinTableName($cm2->associationMappings['user'], $cm2, $this->platform));
    }

    public function testIdentifierColumnNames(): void
    {
        $cm1 = $this->createClassMetadata(CmsAddress::class);
        $cm2 = $this->createClassMetadata(CmsAddress::class);

        $cm1->mapField(
            [
                'id'            => true,
                'fieldName'     => 'id',
                'columnName'    => '`id`',
            ]
        );

        $cm2->mapField(
            [
                'id'            => true,
                'fieldName'     => 'id',
                'columnName'    => 'id',
            ]
        );

        self::assertEquals(['"id"'], $this->strategy->getIdentifierColumnNames($cm1, $this->platform));
        self::assertEquals(['id'], $this->strategy->getIdentifierColumnNames($cm2, $this->platform));
    }

    public function testColumnAlias(): void
    {
        $i = 0;
        self::assertEquals('columnName_0', $this->strategy->getColumnAlias('columnName', $i++, $this->platform));
        self::assertEquals('column_name_1', $this->strategy->getColumnAlias('column_name', $i++, $this->platform));
        self::assertEquals('COLUMN_NAME_2', $this->strategy->getColumnAlias('COLUMN_NAME', $i++, $this->platform));
        self::assertEquals('COLUMNNAME_3', $this->strategy->getColumnAlias('COLUMN-NAME-', $i++, $this->platform));
    }

    public function testQuoteIdentifierJoinColumns(): void
    {
        $cm = $this->createClassMetadata(DDC117ArticleDetails::class);

        $cm->mapOneToOne(
            [
                'id'            => true,
                'fieldName'     => 'article',
                'targetEntity'  => DDC117Article::class,
                'joinColumns'    => [
                    ['name' => '`article`'],
                ],
            ]
        );

        self::assertEquals(['"article"'], $this->strategy->getIdentifierColumnNames($cm, $this->platform));
    }

    public function testJoinColumnName(): void
    {
        $cm = $this->createClassMetadata(DDC117ArticleDetails::class);

        $cm->mapOneToOne(
            [
                'id'            => true,
                'fieldName'     => 'article',
                'targetEntity'  => DDC117Article::class,
                'joinColumns'    => [
                    ['name' => '`article`'],
                ],
            ]
        );

        $joinColumn = $cm->associationMappings['article']['joinColumns'][0];
        self::assertEquals('"article"', $this->strategy->getJoinColumnName($joinColumn, $cm, $this->platform));
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
                    ['name' => '`article`'],
                ],
            ]
        );

        $joinColumn = $cm->associationMappings['article']['joinColumns'][0];
        self::assertEquals('"id"', $this->strategy->getReferencedJoinColumnName($joinColumn, $cm, $this->platform));
    }
}

class MyQuoteStrategy extends DefaultQuoteStrategy
{
}
