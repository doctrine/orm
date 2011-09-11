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
       $this->useModelSet('ddc754');
       parent::setUp();
    }
    
    
    /**
     * @param  string $name
     * @return string 
     */
    private function className($name)
    {
        return 'Doctrine\Tests\Models\DDC754\\'.$name;
    }


    
    /**
     * @group DDC-754
     */
    public function testSelfReferenceMetadata()
    {   
        $factory = new \Doctrine\ORM\Mapping\ClassMetadataFactory();
        $factory->setEntityManager($this->_em);
        
        $class = $factory->getMetadataFor($this->className('DDC754FooEntity'));
        
        $this->assertTrue(isset($class->fieldMappings['id']));
        $this->assertTrue(isset($class->associationMappings['parent']));
        $this->assertEquals($this->className('DDC754FooEntity'),$class->associationMappings['parent']['targetEntity']);
        
        
        $class = $factory->getMetadataFor('Doctrine\Tests\Models\DDC754\DDC754BarEntity');
        
        $this->assertTrue(isset($class->fieldMappings['id']));
        $this->assertTrue(isset($class->associationMappings['parent']));
        $this->assertEquals($this->className('DDC754BarEntity'),$class->associationMappings['parent']['targetEntity']);
    }
    
    public function testSavesSelfAssociationParent()
    {
        $base   = new DDC754BaseTreeEntity("tree root");
        $foo    = new DDC754FooEntity("children foo");
        $bar    = new DDC754BarEntity("children bar");
        
        $this->_em->persist($base);
        $this->_em->flush();
        
        $this->_em->persist($foo);
        $this->_em->flush();
        
        $this->_em->persist($bar);
        $this->_em->flush();
        
        $this->_em->clear();
        
        $base   = $this->_em->find($this->className('DDC754BaseTreeEntity'), $base->getId());
        $foo    = $this->_em->find($this->className('DDC754FooEntity'), $foo->getId());
        $bar    = $this->_em->find($this->className('DDC754BarEntity'), $bar->getId());
        
        
        $foo->setParent($base);
        $bar->setParent($base);
        
        $this->_em->merge($foo);
        $this->_em->merge($foo);
        
        $this->_em->flush();
        $this->_em->clear();
        
        
        $base   = $this->_em->find($this->className('DDC754BaseTreeEntity'), $base->getId());
        $foo    = $this->_em->find($this->className('DDC754FooEntity'), $foo->getId());
        $bar    = $this->_em->find($this->className('DDC754BarEntity'), $bar->getId());
        $list   = $this->_em->getRepository($this->className('DDC754BaseTreeEntity'))->findAll();
        
        
        $this->assertEquals($base->getId(),$foo->getParent()->getId());
        $this->assertEquals($base->getId(),$bar->getParent()->getId());
        
        
        $this->assertEquals(sizeof($list), 3);
        $this->assertInstanceOf($this->className('DDC754BaseTreeEntity'), $list[0]);
        $this->assertInstanceOf($this->className('DDC754FooEntity'), $list[1]);
        $this->assertInstanceOf($this->className('DDC754BarEntity'), $list[2]);
        
        $this->assertForeignKeysContain($foo->getId(),$base->getId());
        $this->assertForeignKeysContain($bar->getId(),$base->getId());
   }
    
    
    public function testSavesSelfAssociationChildren()
    {
        $base   = new DDC754BaseTreeEntity("tree root");
        $foo    = new DDC754FooEntity("children foo");
        $bar    = new DDC754BarEntity("children bar");
        
        $this->_em->persist($base);
        $this->_em->flush();
        $this->_em->clear();
        
        
        $base   = $this->_em->find($this->className('DDC754BaseTreeEntity'), $base->getId());

        $base->addChildren($foo);
        $base->addChildren($bar);
        
        $this->_em->persist($base);
        $this->_em->flush();
        $this->_em->clear();
        
        
        $base   = $this->_em->find($this->className('DDC754BaseTreeEntity'), $base->getId());
        $foo    = $this->_em->find($this->className('DDC754FooEntity'), $foo->getId());
        $bar    = $this->_em->find($this->className('DDC754BarEntity'), $bar->getId());
        $list   = $this->_em->getRepository($this->className('DDC754BaseTreeEntity'))->findAll();
        
        
        $this->assertEquals($base->getId(),$foo->getParent()->getId());
        $this->assertEquals($base->getId(),$bar->getParent()->getId());
        
        
        $this->assertEquals(sizeof($list), 3);
        $this->assertInstanceOf($this->className('DDC754BaseTreeEntity'), $list[0]);
        $this->assertInstanceOf($this->className('DDC754BarEntity'), $list[1]);
        $this->assertInstanceOf($this->className('DDC754FooEntity'), $list[2]);
        
        $this->assertForeignKeysContain($foo->getId(),$base->getId());
        $this->assertForeignKeysContain($bar->getId(),$base->getId());
    }
    
    
    private function _showAll()
    {
        $rs = $this->_em->getConnection()->executeQuery("SELECT * FROM {$this->_table}")->fetchAll(\PDO::FETCH_OBJ);
        
        echo PHP_EOL;
        foreach ($rs as $value) {
            echo "---------------------------------------".PHP_EOL;
            echo "id        : {$value->id}".PHP_EOL;
            echo "name      : {$value->name}".PHP_EOL;
            echo "parent    : {$value->parent_id}".PHP_EOL;
        }
        
        echo "---------------------------------------".PHP_EOL;
    }

}