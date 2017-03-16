<?php

namespace Doctrine\Tests\ORM\Mapping;

use Doctrine\Common\Persistence\Mapping\RuntimeReflectionService;
use Doctrine\ORM\Mapping\ClassMetadata;
use Doctrine\ORM\Mapping\DefaultQuoteStrategy;
use Doctrine\ORM\Mapping\QuoteStrategy;
use Doctrine\Tests\Models\CMS\CmsAddress;
use Doctrine\Tests\Models\CMS\CmsUser;
use Doctrine\Tests\Models\DDC117\DDC117Article;
use Doctrine\Tests\Models\DDC117\DDC117ArticleDetails;
use Doctrine\Tests\OrmTestCase;

/**
 * @group DDC-1845
 */
class QuoteStrategyTest extends OrmTestCase
{

    /**
     * @var \Doctrine\ORM\Mapping\DefaultQuoteStrategy
     */
    private $strategy;

    /**
     * @var \Doctrine\DBAL\Platforms\AbstractPlatform
     */
    private $platform;

    protected function setUp()
    {
        parent::setUp();
        $em = $this->_getTestEntityManager();
        $this->platform = $em->getConnection()->getDatabasePlatform();
        $this->strategy = new DefaultQuoteStrategy();
    }

    /**
     * @param   string $className
     * @return \Doctrine\ORM\Mapping\ClassMetadata
     */
    private function createClassMetadata($className)
    {
        $cm = new ClassMetadata($className);
        $cm->initializeReflection(new RuntimeReflectionService());

        return $cm;
    }

    public function testConfiguration()
    {
        $em     = $this->_getTestEntityManager();
        $config = $em->getConfiguration();

        $this->assertInstanceOf(QuoteStrategy::class, $config->getQuoteStrategy());
        $this->assertInstanceOf(DefaultQuoteStrategy::class, $config->getQuoteStrategy());

        $config->setQuoteStrategy(new MyQuoteStrategy());

        $this->assertInstanceOf(QuoteStrategy::class, $config->getQuoteStrategy());
        $this->assertInstanceOf(MyQuoteStrategy::class, $config->getQuoteStrategy());
    }

    public function testGetColumnName()
    {
        $cm = $this->createClassMetadata(CmsUser::class);
        $cm->mapField(['fieldName' => 'name', 'columnName' => '`name`']);
        $cm->mapField(['fieldName' => 'id', 'columnName' => 'id']);

        $this->assertEquals('id' ,$this->strategy->getColumnName('id', $cm, $this->platform));
        $this->assertEquals('"name"' ,$this->strategy->getColumnName('name', $cm, $this->platform));
    }

    public function testGetTableName()
    {
        $cm = $this->createClassMetadata(CmsUser::class);
        $cm->setPrimaryTable(['name'=>'`cms_user`']);
        $this->assertEquals('"cms_user"', $this->strategy->getTableName($cm, $this->platform));

        $cm = new ClassMetadata(CmsUser::class);
        $cm->initializeReflection(new RuntimeReflectionService());
        $cm->setPrimaryTable(['name'=>'cms_user']);
        $this->assertEquals('cms_user', $this->strategy->getTableName($cm, $this->platform));
    }

    public function testJoinTableName()
    {
        $cm1 = $this->createClassMetadata(CmsAddress::class);
        $cm2 = $this->createClassMetadata(CmsAddress::class);

        $cm1->mapManyToMany(
            [
            'fieldName'     => 'user',
            'targetEntity'  => 'CmsUser',
            'inversedBy'    => 'users',
            'joinTable'     => [
                'name'  => '`cmsaddress_cmsuser`'
            ]
            ]
        );

        $cm2->mapManyToMany(
            [
            'fieldName'     => 'user',
            'targetEntity'  => 'CmsUser',
            'inversedBy'    => 'users',
            'joinTable'     => [
                    'name'  => 'cmsaddress_cmsuser'
            ]
            ]
        );

        $this->assertEquals('"cmsaddress_cmsuser"', $this->strategy->getJoinTableName($cm1->associationMappings['user'], $cm1, $this->platform));
        $this->assertEquals('cmsaddress_cmsuser', $this->strategy->getJoinTableName($cm2->associationMappings['user'], $cm2, $this->platform));

    }

    public function testIdentifierColumnNames()
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

        $this->assertEquals(['"id"'], $this->strategy->getIdentifierColumnNames($cm1, $this->platform));
        $this->assertEquals(['id'], $this->strategy->getIdentifierColumnNames($cm2, $this->platform));
    }


    public function testColumnAlias()
    {
        $i = 0;
        $this->assertEquals('columnName_0', $this->strategy->getColumnAlias('columnName', $i++, $this->platform));
        $this->assertEquals('column_name_1', $this->strategy->getColumnAlias('column_name', $i++, $this->platform));
        $this->assertEquals('COLUMN_NAME_2', $this->strategy->getColumnAlias('COLUMN_NAME', $i++, $this->platform));
        $this->assertEquals('COLUMNNAME_3', $this->strategy->getColumnAlias('COLUMN-NAME-', $i++, $this->platform));
    }

    public function testQuoteIdentifierJoinColumns()
    {
        $cm = $this->createClassMetadata(DDC117ArticleDetails::class);

        $cm->mapOneToOne(
            [
            'id'            => true,
            'fieldName'     => 'article',
            'targetEntity'  => DDC117Article::class,
            'joinColumns'    => [
                [
                'name'  => '`article`'
                ]
            ],
            ]
        );

        $this->assertEquals(['"article"'], $this->strategy->getIdentifierColumnNames($cm, $this->platform));
    }

    public function testJoinColumnName()
    {
        $cm = $this->createClassMetadata(DDC117ArticleDetails::class);

        $cm->mapOneToOne(
            [
            'id'            => true,
            'fieldName'     => 'article',
            'targetEntity'  => DDC117Article::class,
            'joinColumns'    => [
                [
                'name'  => '`article`'
                ]
            ],
            ]
        );

        $joinColumn = $cm->associationMappings['article']['joinColumns'][0];
        $this->assertEquals('"article"',$this->strategy->getJoinColumnName($joinColumn, $cm, $this->platform));
    }

    public function testReferencedJoinColumnName()
    {
        $cm = $this->createClassMetadata(DDC117ArticleDetails::class);

        $cm->mapOneToOne(
            [
            'id'            => true,
            'fieldName'     => 'article',
            'targetEntity'  => DDC117Article::class,
            'joinColumns'    => [
                [
                'name'  => '`article`'
                ]
            ],
            ]
        );

        $joinColumn = $cm->associationMappings['article']['joinColumns'][0];
        $this->assertEquals('"id"',$this->strategy->getReferencedJoinColumnName($joinColumn, $cm, $this->platform));
    }
}

class MyQuoteStrategy extends DefaultQuoteStrategy
{

}
