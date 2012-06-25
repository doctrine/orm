<?php

namespace Doctrine\Tests\ORM\Mapping;

use Doctrine\ORM\Mapping\DefaultQuoteStrategy;
use Doctrine\ORM\Mapping\QuoteStrategy;
use Doctrine\ORM\Mapping\ClassMetadata;

require_once __DIR__ . '/../../TestInit.php';

/**
 * @group DDC-1845
 */
class QuoteStrategyTest extends \Doctrine\Tests\OrmTestCase
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
        $cm->initializeReflection(new \Doctrine\Common\Persistence\Mapping\RuntimeReflectionService);

        return $cm;
    }

    public function testConfiguration()
    {
        $em     = $this->_getTestEntityManager();
        $config = $em->getConfiguration();

        $this->assertInstanceOf('Doctrine\ORM\Mapping\QuoteStrategy', $config->getQuoteStrategy());
        $this->assertInstanceOf('Doctrine\ORM\Mapping\DefaultQuoteStrategy', $config->getQuoteStrategy());

        $config->setQuoteStrategy(new MyQuoteStrategy());

        $this->assertInstanceOf('Doctrine\ORM\Mapping\QuoteStrategy', $config->getQuoteStrategy());
        $this->assertInstanceOf('Doctrine\Tests\ORM\Mapping\MyQuoteStrategy', $config->getQuoteStrategy());
    }

    public function testGetColumnName()
    {
        $cm = $this->createClassMetadata('Doctrine\Tests\Models\CMS\CmsUser');
        $cm->mapField(array('fieldName' => 'name', 'columnName' => '`name`'));
        $cm->mapField(array('fieldName' => 'id', 'columnName' => 'id'));
        
        $this->assertEquals('id' ,$this->strategy->getColumnName('id', $cm, $this->platform));
        $this->assertEquals('"name"' ,$this->strategy->getColumnName('name', $cm, $this->platform));
    }

    public function testGetTableName()
    {
        $cm = $this->createClassMetadata('Doctrine\Tests\Models\CMS\CmsUser');
        $cm->setPrimaryTable(array('name'=>'`cms_user`'));
        $this->assertEquals('"cms_user"' ,$this->strategy->getTableName($cm, $this->platform));

        $cm = new ClassMetadata('Doctrine\Tests\Models\CMS\CmsUser');
        $cm->initializeReflection(new \Doctrine\Common\Persistence\Mapping\RuntimeReflectionService);
        $cm->setPrimaryTable(array('name'=>'cms_user'));
        $this->assertEquals('cms_user' ,$this->strategy->getTableName($cm, $this->platform));
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
                'name'  => '`cmsaddress_cmsuser`'
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

        $this->assertEquals('"cmsaddress_cmsuser"', $this->strategy->getJoinTableName($cm1->associationMappings['user'], $cm1, $this->platform));
        $this->assertEquals('cmsaddress_cmsuser', $this->strategy->getJoinTableName($cm2->associationMappings['user'], $cm2, $this->platform));
       
    }

    public function testIdentifierColumnNames()
    {
        $cm1 = $this->createClassMetadata('Doctrine\Tests\Models\CMS\CmsAddress');
        $cm2 = $this->createClassMetadata('Doctrine\Tests\Models\CMS\CmsAddress');

        $cm1->mapField(array(
            'id'            => true,
            'fieldName'     => 'id',
            'columnName'    => '`id`',
        ));

        $cm2->mapField(array(
            'id'            => true,
            'fieldName'     => 'id',
            'columnName'    => 'id',
        ));

        $this->assertEquals(array('"id"'), $this->strategy->getIdentifierColumnNames($cm1, $this->platform));
        $this->assertEquals(array('id'), $this->strategy->getIdentifierColumnNames($cm2, $this->platform));
    }


    public function testColumnAlias()
    {
        $i = 0;
        $this->assertEquals('columnName0', $this->strategy->getColumnAlias('columnName', $i++, $this->platform));
        $this->assertEquals('column_name1', $this->strategy->getColumnAlias('column_name', $i++, $this->platform));
        $this->assertEquals('COLUMN_NAME2', $this->strategy->getColumnAlias('COLUMN_NAME', $i++, $this->platform));
        $this->assertEquals('COLUMNNAME3', $this->strategy->getColumnAlias('COLUMN-NAME-', $i++, $this->platform));
    }

    public function testQuoteIdentifierJoinColumns()
    {
        $cm = $this->createClassMetadata('Doctrine\Tests\Models\DDC117\DDC117ArticleDetails');

        $cm->mapOneToOne(array(
            'id'            => true,
            'fieldName'     => 'article',
            'targetEntity'  => 'Doctrine\Tests\Models\DDC117\DDC117Article',
            'joinColumns'    => array(array(
                'name'  => '`article`'
            )),
        ));

        $this->assertEquals(array('"article"'), $this->strategy->getIdentifierColumnNames($cm, $this->platform));
    }

    public function testJoinColumnName()
    {
        $cm = $this->createClassMetadata('Doctrine\Tests\Models\DDC117\DDC117ArticleDetails');

        $cm->mapOneToOne(array(
            'id'            => true,
            'fieldName'     => 'article',
            'targetEntity'  => 'Doctrine\Tests\Models\DDC117\DDC117Article',
            'joinColumns'    => array(array(
                'name'  => '`article`'
            )),
        ));

        $joinColumn = $cm->associationMappings['article']['joinColumns'][0];
        $this->assertEquals('"article"',$this->strategy->getJoinColumnName($joinColumn, $cm, $this->platform));
    }

    public function testReferencedJoinColumnName()
    {
        $cm = $this->createClassMetadata('Doctrine\Tests\Models\DDC117\DDC117ArticleDetails');

        $cm->mapOneToOne(array(
            'id'            => true,
            'fieldName'     => 'article',
            'targetEntity'  => 'Doctrine\Tests\Models\DDC117\DDC117Article',
            'joinColumns'    => array(array(
                'name'  => '`article`'
            )),
        ));

        $joinColumn = $cm->associationMappings['article']['joinColumns'][0];
        $this->assertEquals('"id"',$this->strategy->getReferencedJoinColumnName($joinColumn, $cm, $this->platform));
    }
}

class MyQuoteStrategy extends \Doctrine\ORM\Mapping\DefaultQuoteStrategy
{

}