<?php

namespace Doctrine\Tests\ORM\Functional;

require_once __DIR__ . '/../../TestInit.php';
/**
 * Functional tests for the Single Table Inheritance mapping strategy.
 *
 * @author robo
 */
class AssociationKeysChainTest extends \Doctrine\Tests\OrmFunctionalTestCase
{
    public function setUp()
    {
        parent::setUp();
        $this->_schemaTool->createSchema(array(
            $this->_em->getClassMetadata(__NAMESPACE__ . '\\AssociationKeysChain0'),
            $this->_em->getClassMetadata(__NAMESPACE__ . '\\AssociationKeysChainA'),
            $this->_em->getClassMetadata(__NAMESPACE__ . '\\AssociationKeysChainB'),
            $this->_em->getClassMetadata(__NAMESPACE__ . '\\AssociationKeysChainC'),
        ));
    }

    public function testIssue()
    {

        $root = new AssociationKeysChain0();
        $this->_em->persist($root);
        $this->_em->flush();

        $a = new AssociationKeysChainA();
        $a->aId = $root;
        $this->_em->persist($a);
        $this->_em->flush();

        $b = new AssociationKeysChainB();
        $b->bId = $a;
        $this->_em->persist($b);
        $this->_em->flush();


        $c = new AssociationKeysChainC();
        $c->cId = $b;
        $this->_em->persist($c);
        $this->_em->flush();

        $this->_em->clear();


        $root = new AssociationKeysChain0();
        $a = new AssociationKeysChainA();
        $a->aId = $root;
        $b = new AssociationKeysChainB();
        $b->bId = $a;
      

        $cNew = $this->_em->getRepository(__NAMESPACE__ . '\AssociationKeysChainC')->findOneBy(array(
            'cId'=>$b
        ));
        $this->assertNull($cNew);
        
    }
}

/**
 * @Entity
 */
class AssociationKeysChain0
{
    /**
     * @Id @Column(type="integer") @GeneratedValue
     */
    public $id;
}
/**
 * @Entity
 */
class AssociationKeysChainA
{
    /**
     * @OneToOne(targetEntity="AssociationKeysChain0") @id
     * @JoinColumn(name="a_id", referencedColumnName="id")
     */
    public $aId;
}

/**
 * @Entity
 */
class AssociationKeysChainB
{
    /**
     * @OneToOne(targetEntity="AssociationKeysChainA") @id
     * @JoinColumn(name="b_id", referencedColumnName="a_id")
     */
    public $bId;
}
/**
 * @Entity
 */
class AssociationKeysChainC
{
    /**
     * @OneToOne(targetEntity="AssociationKeysChainB") @id
     * @JoinColumn(name="c_id", referencedColumnName="b_id")
     */
    public $cId;
}

