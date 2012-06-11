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

    protected function setUp()
    {
        parent::setUp();
        $em = $this->_getTestEntityManager();
        $this->strategy = new DefaultQuoteStrategy($em->getConnection()->getDatabasePlatform());
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

    public function testGetColumnName()
    {
        $cm = $this->createClassMetadata('Doctrine\Tests\Models\CMS\CmsUser');
        $cm->mapField(array('fieldName' => 'name', 'columnName' => '`name`'));
        $cm->mapField(array('fieldName' => 'id', 'columnName' => 'id'));
        
        $this->assertEquals('id' ,$this->strategy->getColumnName('id', $cm));
        $this->assertEquals('"name"' ,$this->strategy->getColumnName('name', $cm));
    }

    public function testGetTableName()
    {
        $cm = $this->createClassMetadata('Doctrine\Tests\Models\CMS\CmsUser');
        $cm->setPrimaryTable(array('name'=>'`cms_user`'));
        $this->assertEquals('"cms_user"' ,$this->strategy->getTableName($cm));

        $cm = new ClassMetadata('Doctrine\Tests\Models\CMS\CmsUser');
        $cm->initializeReflection(new \Doctrine\Common\Persistence\Mapping\RuntimeReflectionService);
        $cm->setPrimaryTable(array('name'=>'cms_user'));
        $this->assertEquals('cms_user' ,$this->strategy->getTableName($cm));
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

        $this->assertEquals('"cmsaddress_cmsuser"', $this->strategy->getJoinTableName($cm1->associationMappings['user'], $cm1));
        $this->assertEquals('cmsaddress_cmsuser', $this->strategy->getJoinTableName($cm2->associationMappings['user'], $cm2));
       
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

        $this->assertEquals(array('"id"'), $this->strategy->getIdentifierColumnNames($cm1));
        $this->assertEquals(array('id'), $this->strategy->getIdentifierColumnNames($cm2));
    }


    public function testColumnAlias()
    {
        $i = 0;
        $this->assertEquals('columnName0', $this->strategy->getColumnAlias('columnName', $i++));
        $this->assertEquals('column_name1', $this->strategy->getColumnAlias('column_name', $i++));
        $this->assertEquals('COLUMN_NAME2', $this->strategy->getColumnAlias('COLUMN_NAME', $i++));
        $this->assertEquals('COLUMNNAME3', $this->strategy->getColumnAlias('COLUMN-NAME-', $i++));
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

        $this->assertEquals(array('"article"'), $this->strategy->getIdentifierColumnNames($cm));
    }
}