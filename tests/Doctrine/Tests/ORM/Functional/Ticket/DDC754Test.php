<?php

namespace Doctrine\Tests\ORM\Functional\Ticket;

require_once __DIR__ . '/../../../TestInit.php';

use Doctrine\Tests\ORM\Functional\AbstractManyToManyAssociationTestCase;
use Doctrine\Tests\Models\DDC754\DDC754FooEntity;
use Doctrine\Tests\Models\DDC754\DDC754BarEntity;
use Doctrine\Tests\Models\DDC754\DDC754BaseTreeEntity;

/**
 * @group DDC-754
 */
class DDC754Test extends AbstractManyToManyAssociationTestCase
{
    protected $_firstField  = 'id';
    protected $_secondField = 'parent_id';
    protected $_table       = 'ddc754_tree';
    
    protected function setUp()
    {
        parent::setUp();
    }
    /**
     * @group DDC-754
     */
    public function testSelfReferenceMetadata()
    {   
        $factory = new \Doctrine\ORM\Mapping\ClassMetadataFactory();
        $factory->setEntityManager($this->_em);
        
        $class = $factory->getMetadataFor('Doctrine\Tests\Models\DDC754\DDC754FooEntity');
        
        $this->assertTrue(isset($class->fieldMappings['id']));
        $this->assertTrue(isset($class->associationMappings['parent']));
        $this->assertEquals('Doctrine\Tests\Models\DDC754\DDC754FooEntity',$class->associationMappings['parent']['targetEntity']);
        
        
        $class = $factory->getMetadataFor('Doctrine\Tests\Models\DDC754\DDC754BarEntity');
        
        $this->assertTrue(isset($class->fieldMappings['id']));
        $this->assertTrue(isset($class->associationMappings['parent']));
        $this->assertEquals('Doctrine\Tests\Models\DDC754\DDC754BarEntity',$class->associationMappings['parent']['targetEntity']);
    }
    
    
    public function testSavesSelfAssociation()
    {
        $this->markTestIncomplete();
        $foo = new DDC754FooEntity();
        $bar = new DDC754BarEntity();
        
        $foo->setParent($bar);
        
        $this->_em->persist($foo);
        $this->_em->flush();
        
        $this->assertForeignKeysContain($foo->getId(),$bar>getId());
    }

}