<?php

namespace Doctrine\Tests\ORM\Functional;

use Doctrine\Tests\Models\ECommerce\ECommerceCategory;

require_once __DIR__ . '/../../TestInit.php';

/**
 * Tests a bidirectional one-to-one association mapping (without inheritance).
 */
class OneToManySelfReferentialAssociationTest extends \Doctrine\Tests\OrmFunctionalTestCase
{
    private $parent;
    private $firstChild;
    private $secondChild;

    protected function setUp()
    {
        $this->useModelSet('ecommerce');
        parent::setUp();
        $this->parent = new ECommerceCategory();
        $this->parent->setName('Programming languages books');
        $this->firstChild = new ECommerceCategory();
        $this->firstChild->setName('Java books');
        $this->secondChild = new ECommerceCategory();
        $this->secondChild->setName('Php books');
    }

    public function testSavesAOneToManyAssociationWithCascadeSaveSet() {
        $this->parent->addChild($this->firstChild);
        $this->parent->addChild($this->secondChild);
        $this->_em->save($this->parent);
        
        $this->assertForeignKeyIs($this->parent->getId(), $this->firstChild);
        $this->assertForeignKeyIs($this->parent->getId(), $this->secondChild);
    }

    public function testSavesAnEmptyCollection()
    {
        $this->_em->save($this->parent);

        $this->assertEquals(0, count($this->parent->getChildren()));
    }

    public function testDoesNotSaveAnInverseSideSet() {
        $this->parent->brokenAddChild($this->firstChild);
        $this->_em->save($this->parent);
        
        $this->assertForeignKeyIs(null, $this->firstChild);
    }

    public function testRemovesOneToManyAssociation()
    {
        $this->parent->addChild($this->firstChild);
        $this->parent->addChild($this->secondChild);
        $this->_em->save($this->parent);

        $this->parent->removeChild($this->firstChild);
        $this->_em->flush();

        $this->assertForeignKeyIs(null, $this->firstChild);
        $this->assertForeignKeyIs($this->parent->getId(), $this->secondChild);
    }

    public function testEagerLoadsOneToManyAssociation()
    {
        $this->parent->addChild($this->firstChild);
        $this->parent->addChild($this->secondChild);
        $this->_em->save($this->parent);
        
        $this->_em->flush();
        $this->_em->clear();

        $query = $this->_em->createQuery('select c1, c2 from Doctrine\Tests\Models\ECommerce\ECommerceCategory c1 join c1.children c2');
        $result = $query->getResultList();
        $this->assertEquals(1, count($result));
        $parent = $result[0];
        $children = $parent->getChildren();
        
        $this->assertTrue($children[0] instanceof ECommerceCategory);
        $this->assertSame($parent, $children[0]->getParent());
        $this->assertTrue(strstr($children[0]->getName(), ' books'));
        $this->assertTrue($children[1] instanceof ECommerceCategory);
        $this->assertSame($parent, $children[1]->getParent());
        $this->assertTrue(strstr($children[1]->getName(), ' books'));
    }
    
    /* TODO: not yet implemented
    public function testLazyLoad() {
        
    }*/

    public function assertForeignKeyIs($value, ECommerceCategory $child) {
        $foreignKey = $this->_em->getConnection()->execute('SELECT parent_id FROM ecommerce_categories WHERE id=?', array($child->getId()))->fetchColumn();
        $this->assertEquals($value, $foreignKey);
    }
}
