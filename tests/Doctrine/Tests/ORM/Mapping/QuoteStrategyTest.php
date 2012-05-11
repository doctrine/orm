<?php

namespace Doctrine\Tests\ORM\Mapping;

use Doctrine\ORM\Mapping\DefaultQuoteStrategy;
use Doctrine\ORM\Mapping\QuoteStrategy;
use Doctrine\ORM\Mapping\ClassMetadata;

require_once __DIR__ . '/../../TestInit.php';

/**
 * @group DDC-1719
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


    public function testIsQuotedIdentifier()
    {
        $this->assertTrue($this->strategy->isQuotedIdentifier('`table_name`'));
        $this->assertFalse($this->strategy->isQuotedIdentifier('table_name'));
        $this->assertFalse($this->strategy->isQuotedIdentifier(null));
        $this->assertFalse($this->strategy->isQuotedIdentifier(''));
    }

    public function testGetUnquotedIdentifier()
    {
        $this->assertEquals('table_name' ,$this->strategy->getUnquotedIdentifier('`table_name`'));
        $this->assertEquals('table_name' ,$this->strategy->getUnquotedIdentifier('table_name'));
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
        $cm = $this->createClassMetadata('Doctrine\Tests\Models\CMS\CmsAddress');
        $cm->mapManyToMany(array(
            'fieldName'     => 'user',
            'targetEntity'  => 'CmsUser',
            'inversedBy'    => 'users',
            'joinTable'     => array(
                    'name'  => '`cmsaddress_cmsuser`'
                )
            )
        );
        $this->assertEquals('"cmsaddress_cmsuser"', $this->strategy->getJoinTableName('user', $cm));
        
        $cm = $this->createClassMetadata('Doctrine\Tests\Models\CMS\CmsAddress');
        $cm->mapManyToMany(array(
            'fieldName'     => 'user',
            'targetEntity'  => 'CmsUser',
            'inversedBy'    => 'users',
            'joinTable'     => array(
                    'name'  => 'cmsaddress_cmsuser'
                )
            )
        );
        $this->assertEquals('cmsaddress_cmsuser', $this->strategy->getJoinTableName('user', $cm));

    }
}