<?php

namespace Doctrine\Tests\ORM\Functional\Ticket;

/**
 * @group DDC-1548
 */
class DDC1548Test extends \Doctrine\Tests\OrmFunctionalTestCase
{
    public function setUp()
    {
        parent::setUp();
        $this->_schemaTool->createSchema(array(
            $this->_em->getClassMetadata(__NAMESPACE__ . '\\DDC1548E1'),
            $this->_em->getClassMetadata(__NAMESPACE__ . '\\DDC1548E2'),
            $this->_em->getClassMetadata(__NAMESPACE__ . '\\DDC1548Rel'),
        ));
    }

    public function testIssue()
    {
        $rel = new DDC1548Rel();
        $this->_em->persist($rel);
        $this->_em->flush();

        $e1 = new DDC1548E1();
        $e1->rel = $rel;
        $this->_em->persist($e1);
        $this->_em->flush();
        $this->_em->clear();

        $obt = $this->_em->find(__NAMESPACE__ . '\DDC1548Rel', $rel->id);

        $this->assertNull($obt->e2);
    }
}

/**
 * @Entity
 */
class DDC1548E1
{
    /**
     * @Id
     * @OneToOne(targetEntity="DDC1548Rel", inversedBy="e1")
     */
    public $rel;
}

/**
 * @Entity
 */
class DDC1548E2
{
    /**
     * @Id
     * @OneToOne(targetEntity="DDC1548Rel", inversedBy="e2")
     */
    public $rel;
}

/**
 * @Entity
 */
class DDC1548Rel
{
    /**
     * @Id @GeneratedValue
     * @Column(type="integer")
     */
    public $id;

    /**
     * @OneToOne(targetEntity="DDC1548E1", mappedBy="rel")
     */
    public $e1;
    /**
     * @OneToOne(targetEntity="DDC1548E2", mappedBy="rel")
     */
    public $e2;
}