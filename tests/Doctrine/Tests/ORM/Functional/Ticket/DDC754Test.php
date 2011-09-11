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
    
    /**
     * @var DDC754BaseTreeEntity
     */
    protected $parent;
    /**
     * @var DDC754BaseTreeEntity
     */
    protected $base;
    /**
     * @var DDC754FooEntity
     */
    protected $foo;
    /**
     * @var DDC754BarEntity
     */
    protected $bar;
    
    protected function setUp()
    {
       $this->parent = new DDC754BaseTreeEntity("0 tree root");
       $this->base   = new DDC754BaseTreeEntity("1 children base");
       $this->foo    = new DDC754FooEntity("2 children foo");
       $this->bar    = new DDC754BarEntity("3 children bar");
       
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
    
    /**
     * @group DDC-754
     */
    public function testSavesSelfManyToOne()
    {
        $this->_em->persist($this->parent);
        $this->_em->persist($this->base);
        $this->_em->persist($this->foo);
        $this->_em->persist($this->bar);
        
        $this->_em->flush();
        $this->_em->clear();
        
        
        $parent = $this->_em->find($this->className('DDC754BaseTreeEntity'),    $this->parent->getId());
        $base   = $this->_em->find($this->className('DDC754BaseTreeEntity'),    $this->base->getId());
        $foo    = $this->_em->find($this->className('DDC754FooEntity'),         $this->foo->getId());
        $bar    = $this->_em->find($this->className('DDC754BarEntity'),         $this->bar->getId());
        
        
        $foo->setParent($parent);
        $bar->setParent($parent);
        $base->setParent($parent);
        
        
        $this->_em->merge($foo);
        $this->_em->merge($bar);
        $this->_em->merge($base);
        
        $this->_em->flush();
        
        $this->assertTree($parent, $foo, $bar, $base);
   }
    
    /**
     * @group DDC-754
     */
    public function testSavesSelfOneToMany()
    {
        $this->_em->persist($this->parent);
        $this->_em->flush();
        $this->_em->clear();
        
        $parent = $this->_em->find($this->className('DDC754BaseTreeEntity'),    $this->parent->getId());

        $parent->addChild($this->foo);
        $parent->addChild($this->bar);
        $parent->addChild($this->base);
        
        $this->_em->persist($parent);
        $this->_em->flush();
        
        $base   = $this->_em->find($this->className('DDC754BaseTreeEntity'),    $this->base->getId());
        $foo    = $this->_em->find($this->className('DDC754FooEntity'),         $this->foo->getId());
        $bar    = $this->_em->find($this->className('DDC754BarEntity'),         $this->bar->getId());

        $this->assertTree($parent, $foo, $bar, $base);
    }
    
    private function assertTree(DDC754BaseTreeEntity $parent,DDC754FooEntity $foo, 
                                DDC754BarEntity $bar, DDC754BaseTreeEntity $base)
    {
        
        $this->_em->clear();
        
        $this->assertForeignKeysContain($foo->getId(),$parent->getId());
        $this->assertForeignKeysContain($bar->getId(),$parent->getId());
        $this->assertForeignKeysContain($base->getId(),$parent->getId());
        
        $this->assertEquals($parent->getId(),$foo->getParent()->getId());
        $this->assertEquals($parent->getId(),$bar->getParent()->getId());
        $this->assertEquals($parent->getId(),$base->getParent()->getId());
        
        
        $parent = $this->_em->find($this->className('DDC754BaseTreeEntity'), $parent->getId());
        $foo    = $this->_em->find($this->className('DDC754FooEntity'), $foo->getId());
        $bar    = $this->_em->find($this->className('DDC754BarEntity'), $bar->getId());
        $base   = $this->_em->find($this->className('DDC754BaseTreeEntity'), $base->getId());
        $list   = $this->_em->getRepository($this->className('DDC754BaseTreeEntity'))
                            ->createNamedQuery("ddc754_all")->execute();
        
        
        $this->assertEquals(sizeof($list), 4);
        $this->assertInstanceOf($this->className('DDC754BaseTreeEntity'),   $list[0]);
        $this->assertInstanceOf($this->className('DDC754BaseTreeEntity'),   $list[1]);
        $this->assertInstanceOf($this->className('DDC754FooEntity'),        $list[2]);
        $this->assertInstanceOf($this->className('DDC754BarEntity'),        $list[3]);
        
        
        $this->assertEquals(sizeof($parent->getChildren()), 3);
        $this->assertInstanceOf($this->className('DDC754BaseTreeEntity'),$parent->getChildren()->get(0));
        $this->assertInstanceOf($this->className('DDC754FooEntity'),$parent->getChildren()->get(1));
        $this->assertInstanceOf($this->className('DDC754BarEntity'),$parent->getChildren()->get(2));
    }

}