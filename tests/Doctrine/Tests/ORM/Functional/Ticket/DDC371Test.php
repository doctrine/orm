<?php
namespace Doctrine\Tests\ORM\Functional\Ticket;

use Doctrine\ORM\Query;

require_once __DIR__ . '/../../../TestInit.php';

class DDC371Test extends \Doctrine\Tests\OrmFunctionalTestCase
{
    protected function setUp()
    {
        parent::setUp();
        //$this->_em->getConnection()->getConfiguration()->setSQLLogger(new \Doctrine\DBAL\Logging\EchoSQLLogger);
        $this->_schemaTool->createSchema(array(
            $this->_em->getClassMetadata(__NAMESPACE__ . '\DDC371Parent'),
            $this->_em->getClassMetadata(__NAMESPACE__ . '\DDC371Child')
        ));
    }

    public function testIssue()
    {
        $parent = new DDC371Parent;
        $parent->data = 'parent';
        $parent->children = new \Doctrine\Common\Collections\ArrayCollection;
        
        $child = new DDC371Child;
        $child->data = 'child';
        
        $child->parent = $parent;
        $parent->children->add($child);
        
        $this->_em->persist($parent);
        $this->_em->persist($child);
        
        $this->_em->flush();
        $this->_em->clear();
        
        $children = $this->_em->createQuery('select c,p from '.__NAMESPACE__.'\DDC371Child c '
                . 'left join c.parent p where c.id = 1 and p.id = 1')
                ->setHint(Query::HINT_REFRESH, true)
                ->getResult();
                
        $this->assertEquals(1, count($children));
        $this->assertFalse($children[0]->parent instanceof \Doctrine\ORM\Proxy\Proxy);
        $this->assertFalse($children[0]->parent->children->isInitialized());
        $this->assertEquals(0, $children[0]->parent->children->unwrap()->count());
    }
}

/** @Entity */
class DDC371Child {
    /** @Id @Column(type="integer") @GeneratedValue */
    private $id;
    /** @Column(type="string") */
    public $data;
    /** @ManyToOne(targetEntity="DDC371Parent", inversedBy="children") @JoinColumn(name="parentId") */
    public $parent;
}

/** @Entity */
class DDC371Parent {
    /** @Id @Column(type="integer") @GeneratedValue */
    private $id;
    /** @Column(type="string") */
    public $data;
    /** @OneToMany(targetEntity="DDC371Child", mappedBy="parent") */
    public $children;
}

