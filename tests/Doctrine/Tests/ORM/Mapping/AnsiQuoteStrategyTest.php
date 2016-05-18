<?php

namespace Doctrine\Tests\ORM\Mapping;

use Doctrine\Common\Persistence\Mapping\RuntimeReflectionService;
use Doctrine\DBAL\Types\Type;
use Doctrine\ORM\Mapping\AnsiQuoteStrategy;
use Doctrine\ORM\Mapping\ClassMetadata;
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

        $em             = $this->_getTestEntityManager();
        $this->platform = $em->getConnection()->getDatabasePlatform();
        $this->strategy = new AnsiQuoteStrategy();
    }

    /**
     * @param   string $className
     * @return \Doctrine\ORM\Mapping\ClassMetadata
     */
    private function createClassMetadata($className)
    {
        $class = new ClassMetadata($className);
        $class->initializeReflection(new RuntimeReflectionService());

        return $class;
    }

    public function testGetColumnName()
    {
        $class = $this->createClassMetadata(CmsUser::class);
        $class->addProperty('id', Type::getType('string'), ['id' => true]);
        $class->addProperty('name', Type::getType('string'));

        self::assertEquals('id' ,$this->strategy->getColumnName('id', $class, $this->platform));
        self::assertEquals('name' ,$this->strategy->getColumnName('name', $class, $this->platform));
    }

    public function testGetTableName()
    {
        $class = $this->createClassMetadata(CmsUser::class);

        $class->setPrimaryTable(['name'=>'cms_user']);
        self::assertEquals('cms_user' ,$this->strategy->getTableName($class, $this->platform));
    }

    public function testJoinTableName()
    {
        $class = $this->createClassMetadata(CmsAddress::class);

        $class->mapManyToMany(
            [
                'fieldName'     => 'user',
                'targetEntity'  => 'CmsUser',
                'inversedBy'    => 'users',
                'joinTable'     => ['name'  => 'cmsaddress_cmsuser']
            ]
        );

        self::assertEquals('cmsaddress_cmsuser', $this->strategy->getJoinTableName($class->associationMappings['user'], $class, $this->platform));
    }

    public function testIdentifierColumnNames()
    {
        $class = $this->createClassMetadata(CmsAddress::class);
        $class->addProperty('id', Type::getType('integer'), ['id' => true]);

        self::assertEquals(['id'], $this->strategy->getIdentifierColumnNames($class, $this->platform));
    }


    public function testColumnAlias()
    {
        self::assertEquals('columnName_1', $this->strategy->getColumnAlias('columnName', 1, $this->platform));
    }

    public function testJoinColumnName()
    {
        $class = $this->createClassMetadata(DDC117ArticleDetails::class);

        $class->mapOneToOne(
            [
            'id'            => true,
            'fieldName'     => 'article',
            'targetEntity'  => DDC117Article::class,
            'joinColumns'    => [
                [
                'name'  => 'article'
                ]
            ],
            ]
        );

        $joinColumn = $class->associationMappings['article']['joinColumns'][0];
        self::assertEquals('article',$this->strategy->getJoinColumnName($joinColumn, $class, $this->platform));
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
                'name'  => 'article'
                ]
            ],
            ]
        );

        $joinColumn = $cm->associationMappings['article']['joinColumns'][0];
        self::assertEquals('id',$this->strategy->getReferencedJoinColumnName($joinColumn, $cm, $this->platform));
    }

    public function testGetSequenceName()
    {
        $class      = $this->createClassMetadata(CmsUser::class);
        $definition = [
            'sequenceName'      => 'user_id_seq',
            'allocationSize'    => 1,
            'initialValue'      => 2
        ];

        $class->setSequenceGeneratorDefinition($definition);

        self::assertEquals('user_id_seq',$this->strategy->getSequenceName($definition, $class, $this->platform));
    }
}
