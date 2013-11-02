<?php

namespace Doctrine\Tests\ORM\Mapping;

use Doctrine\ORM\Mapping\AnsiQuoteStrategy;
use Doctrine\ORM\Mapping\ClassMetadata;
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
        $class->initializeReflection(new \Doctrine\Common\Persistence\Mapping\RuntimeReflectionService);

        return $class;
    }

    public function testGetColumnName()
    {
        $class = $this->createClassMetadata('Doctrine\Tests\Models\CMS\CmsUser');
        $class->mapField(array('fieldName' => 'name', 'columnName' => 'name'));
        $class->mapField(array('fieldName' => 'id', 'columnName' => 'id', 'id' => true));

        $this->assertEquals('id' ,$this->strategy->getColumnName('id', $class, $this->platform));
        $this->assertEquals('name' ,$this->strategy->getColumnName('name', $class, $this->platform));
    }

    public function testGetTableName()
    {
        $class = $this->createClassMetadata('Doctrine\Tests\Models\CMS\CmsUser');

        $class->setPrimaryTable(array('name'=>'cms_user'));
        $this->assertEquals('cms_user' ,$this->strategy->getTableName($class, $this->platform));
    }

    public function testJoinTableName()
    {
        $class = $this->createClassMetadata('Doctrine\Tests\Models\CMS\CmsAddress');
        
        $class->mapManyToMany(array(
            'fieldName'     => 'user',
            'targetEntity'  => 'CmsUser',
            'inversedBy'    => 'users',
            'joinTable'     => array(
                'name'  => 'cmsaddress_cmsuser'
            )
        ));
        
        $this->assertEquals('cmsaddress_cmsuser', $this->strategy->getJoinTableName($class->associationMappings['user'], $class, $this->platform));
       
    }

    public function testIdentifierColumnNames()
    {
        $class = $this->createClassMetadata('Doctrine\Tests\Models\CMS\CmsAddress');

        $class->mapField(array(
            'id'            => true,
            'fieldName'     => 'id',
            'columnName'    => 'id',
        ));

        $this->assertEquals(array('id'), $this->strategy->getIdentifierColumnNames($class, $this->platform));
    }


    public function testColumnAlias()
    {
        $this->assertEquals('columnName1', $this->strategy->getColumnAlias('columnName', 1, $this->platform));
    }

    public function testJoinColumnName()
    {
        $class = $this->createClassMetadata('Doctrine\Tests\Models\DDC117\DDC117ArticleDetails');

        $class->mapOneToOne(array(
            'id'            => true,
            'fieldName'     => 'article',
            'targetEntity'  => 'Doctrine\Tests\Models\DDC117\DDC117Article',
            'joinColumns'    => array(array(
                'name'  => 'article'
            )),
        ));

        $joinColumn = $class->associationMappings['article']['joinColumns'][0];
        $this->assertEquals('article',$this->strategy->getJoinColumnName($joinColumn, $class, $this->platform));
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
        $this->assertEquals('id',$this->strategy->getReferencedJoinColumnName($joinColumn, $cm, $this->platform));
    }

    public function testGetSequenceName()
    {
        $class      = $this->createClassMetadata('Doctrine\Tests\Models\CMS\CmsUser');
        $definition = array(
            'sequenceName'      => 'user_id_seq',
            'allocationSize'    => 1,
            'initialValue'      => 2
        );

        $class->setSequenceGeneratorDefinition($definition);

        $this->assertEquals('user_id_seq',$this->strategy->getSequenceName($definition, $class, $this->platform));
    }
}