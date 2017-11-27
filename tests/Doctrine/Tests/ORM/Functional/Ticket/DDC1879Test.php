<?php

namespace Doctrine\Tests\ORM\Functional\Ticket;

use Doctrine\Tests\Models\DDC117\DDC117ArticleDetails;
use Doctrine\Tests\Models\DDC117\DDC117Article;
use Doctrine\Tests\Models\DDC117\DDC117Reference;
use Doctrine\Tests\Models\DDC117\DDC117Translation;
use Doctrine\Tests\Models\DDC117\DDC117ApproveChanges;
use Doctrine\Tests\Models\DDC117\DDC117Editor;
use Doctrine\Tests\Models\DDC117\DDC117Link;
use Doctrine\Tests\Models\DDC1879\DDC1879Child;
use Doctrine\Tests\Models\DDC1879\DDC1879Parent;

/**
 * @group DDC-1879
 */
class DDC1879Test extends \Doctrine\Tests\OrmFunctionalTestCase
{
    private $parent;
    private $child1;
    private $child2;
    private $child3;

    protected function setUp()
    {
        $this->useModelSet('ddc1879');
        parent::setUp();

        $this->parent         = new DDC1879Parent();

        $this->child1         = new DDC1879Child();
        $this->child1->id     = 1;
        $this->child1->value  = 'child1';
        $this->parent->children->add($this->child1);
        $this->child1->parent = $this->parent;

        $this->child2         = new DDC1879Child();
        $this->child2->id     = 2;
        $this->child2->value  = 'child2';
        $this->parent->children->add($this->child2);
        $this->child2->parent = $this->parent;

        $this->child3         = new DDC1879Child();
        $this->child3->id     = 3;
        $this->child3->value  = 'child3';
        $this->parent->children->add($this->child3);
        $this->child3->parent = $this->parent;

        $this->_em->persist($this->child1);
        $this->_em->persist($this->child2);
        $this->_em->persist($this->child3);
        $this->_em->persist($this->parent);
        $this->_em->flush();

        $this->_em->clear();
    }

    /**
     * A test case outlining the Issue https://github.com/doctrine/doctrine2/issues/2542
     *
     * $em->merge() should remove orphans from a OneToMany relation if orphanRemoval == true
     *
     * @group DDC-1879
     */
    public function testOrphanRemoval()
    {
        $newParent     = new DDC1879Parent();
        $newParent->id = $this->parent->id;

        $newChild2Value    = 'child2_new';

        $newChild2         = new DDC1879Child();
        $newChild2->id     = $this->child2->id;
        $newParent->children->add($newChild2);
        $newChild2->parent = $newParent;
        $newChild2->value  = $newChild2Value;

        $newChild4         = new DDC1879Child();
        // no ID set!
        $newParent->children->add($newChild4);
        $newChild4->parent = $newParent;
        $newChild4->value  = 'child4';

        $savedParent = $this->_em->merge($newParent);
        $this->_em->flush();

        self::assertCount(2, $savedParent->children);

        $this->_em->clear();

        $parentFromDb = $this->_em->find(DDC1879Parent::class, ['id' => $this->parent->id]);

        self::assertNotNull($parentFromDb);
        self::assertCount(2, $parentFromDb->children);

        foreach ($parentFromDb->children as $child) {
            self::assertContains($child->id, [2, 4]);

            if ($child->id === 2) {
                self::assertEquals($newChild2Value, $child->value);
            }
        }
    }
}
