<?php

namespace Doctrine\Tests\ORM\Mapping;

use Doctrine\Common\Persistence\Mapping\RuntimeReflectionService;
use Doctrine\DBAL\Types\Type;
use Doctrine\ORM\Mapping\DefaultQuoteStrategy;
use Doctrine\ORM\Mapping\ClassMetadata;
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

        self::assertInstanceOf('Doctrine\ORM\Mapping\QuoteStrategy', $config->getQuoteStrategy());
        self::assertInstanceOf('Doctrine\ORM\Mapping\DefaultQuoteStrategy', $config->getQuoteStrategy());

        $config->setQuoteStrategy(new MyQuoteStrategy());

        self::assertInstanceOf('Doctrine\ORM\Mapping\QuoteStrategy', $config->getQuoteStrategy());
        self::assertInstanceOf('Doctrine\Tests\ORM\Mapping\MyQuoteStrategy', $config->getQuoteStrategy());
    }

    public function testGetColumnName()
    {
        $cm = $this->createClassMetadata('Doctrine\Tests\Models\CMS\CmsUser');

        $idProperty   = $cm->addProperty('id', Type::getType('string'));
        $nameProperty = $cm->addProperty('name', Type::getType('string'), array('columnName' => 'name'));
        
        self::assertEquals('id', $this->strategy->getColumnName($idProperty, $this->platform));
        self::assertEquals('"name"', $this->strategy->getColumnName($nameProperty, $this->platform));
    }

    public function testGetTableName()
    {
        $cm = $this->createClassMetadata('Doctrine\Tests\Models\CMS\CmsUser');

        $cm->setPrimaryTable(array('name'=>'cms_user'));

        self::assertEquals('"cms_user"', $this->strategy->getTableName($cm, $this->platform));

        $cm = new ClassMetadata('Doctrine\Tests\Models\CMS\CmsUser');

        $cm->initializeReflection(new RuntimeReflectionService());
        $cm->setPrimaryTable(array('name'=>'cms_user'));

        self::assertEquals('cms_user', $this->strategy->getTableName($cm, $this->platform));
    }
    
    public function testJoinTableName()
    {
        $cm1 = $this->createClassMetadata('Doctrine\Tests\Models\CMS\CmsAddress');
        $cm2 = $this->createClassMetadata('Doctrine\Tests\Models\CMS\CmsAddress');
        
        $cm1->mapManyToMany(array(
            'fieldName'     => 'user',
            'targetEntity'  => 'CmsUser',
            'inversedBy'    => 'users',
            'joinTable'     => array(
                'name'  => 'cmsaddress_cmsuser'
            )
        ));
        
        $cm2->mapManyToMany(array(
            'fieldName'     => 'user',
            'targetEntity'  => 'CmsUser',
            'inversedBy'    => 'users',
            'joinTable'     => array(
                    'name'  => 'cmsaddress_cmsuser'
                )
            )
        );

        self::assertEquals('"cmsaddress_cmsuser"', $this->strategy->getJoinTableName($cm1->associationMappings['user'], $cm1, $this->platform));
        self::assertEquals('cmsaddress_cmsuser', $this->strategy->getJoinTableName($cm2->associationMappings['user'], $cm2, $this->platform));
       
    }

    public function testIdentifierColumnNames()
    {
        $cm1 = $this->createClassMetadata('Doctrine\Tests\Models\CMS\CmsAddress');
        $cm2 = $this->createClassMetadata('Doctrine\Tests\Models\CMS\CmsAddress');

        $cm1->addProperty('id', Type::getType('string'), array(
            'id'         => true,
            'columnName' => 'id',
        ));

        $cm2->addProperty('id', Type::getType('string'), array(
            'id'         => true,
            'columnName' => 'id',
        ));

        self::assertEquals(array('"id"'), $this->strategy->getIdentifierColumnNames($cm1, $this->platform));
        self::assertEquals(array('id'), $this->strategy->getIdentifierColumnNames($cm2, $this->platform));
    }


    public function testColumnAlias()
    {
        $i = 0;
        self::assertEquals('columnName_0', $this->strategy->getColumnAlias('columnName', $i++, $this->platform));
        self::assertEquals('column_name_1', $this->strategy->getColumnAlias('column_name', $i++, $this->platform));
        self::assertEquals('COLUMN_NAME_2', $this->strategy->getColumnAlias('COLUMN_NAME', $i++, $this->platform));
        self::assertEquals('COLUMNNAME_3', $this->strategy->getColumnAlias('COLUMN-NAME-', $i++, $this->platform));
    }

    public function testQuoteIdentifierJoinColumns()
    {
        $cm = $this->createClassMetadata('Doctrine\Tests\Models\DDC117\DDC117ArticleDetails');

        $cm->mapOneToOne(array(
            'id'            => true,
            'fieldName'     => 'article',
            'targetEntity'  => 'Doctrine\Tests\Models\DDC117\DDC117Article',
            'joinColumns'    => array(array(
                'name'  => 'article'
            )),
        ));

        self::assertEquals(array('"article"'), $this->strategy->getIdentifierColumnNames($cm, $this->platform));
    }

    public function testJoinColumnName()
    {
        $cm = $this->createClassMetadata('Doctrine\Tests\Models\DDC117\DDC117ArticleDetails');

        $cm->mapOneToOne(array(
            'id'            => true,
            'fieldName'     => 'article',
            'targetEntity'  => 'Doctrine\Tests\Models\DDC117\DDC117Article',
            'joinColumns'    => array(array(
                'name'  => 'article'
            )),
        ));

        $joinColumn = $cm->associationMappings['article']['joinColumns'][0];
        self::assertEquals('"article"',$this->strategy->getJoinColumnName($joinColumn, $cm, $this->platform));
    }

    public function testReferencedJoinColumnName()
    {
        $cm = $this->createClassMetadata('Doctrine\Tests\Models\DDC117\DDC117ArticleDetails');

        $cm->mapOneToOne(array(
            'id'            => true,
            'fieldName'     => 'article',
            'targetEntity'  => 'Doctrine\Tests\Models\DDC117\DDC117Article',
            'joinColumns'    => array(array(
                'name'  => 'article'
            )),
        ));

        $joinColumn = $cm->associationMappings['article']['joinColumns'][0];
        self::assertEquals('"id"',$this->strategy->getReferencedJoinColumnName($joinColumn, $cm, $this->platform));
    }
}

class MyQuoteStrategy extends DefaultQuoteStrategy
{

}
