<?php

namespace Doctrine\Tests\ORM\Functional\Ticket;

use Doctrine\Tests\OrmFunctionalTestCase;

class DDC448Test extends OrmFunctionalTestCase
{
    protected function setUp()
    {
        parent::setUp();
        $this->_schemaTool->createSchema(
            [
            $this->_em->getClassMetadata(DDC448MainTable::class),
            $this->_em->getClassMetadata(DDC448ConnectedClass::class),
            $this->_em->getClassMetadata(DDC448SubTable::class),
            ]
        );
    }

    public function testIssue()
    {
        $q = $this->_em->createQuery("select b from ".__NAMESPACE__."\\DDC448SubTable b where b.connectedClassId = ?1");
        $this->assertEquals(
            strtolower('SELECT d0_.id AS id_0, d0_.discr AS discr_1, d0_.connectedClassId AS connectedClassId_2 FROM SubTable s1_ INNER JOIN DDC448MainTable d0_ ON s1_.id = d0_.id WHERE d0_.connectedClassId = ?'),
            strtolower($q->getSQL())
        );
    }
}

/**
 * @Entity
 * @InheritanceType("JOINED")
 * @DiscriminatorColumn(name="discr", type="smallint")
 * @DiscriminatorMap({
 *     "0" = "DDC448MainTable",
 *     "1" = "DDC448SubTable"
 * })
 */
class DDC448MainTable
{
    /**
     * @Id
     * @Column(name="id", type="integer")
     * @GeneratedValue(strategy="AUTO")
     */
    private $id;

    /**
     * @ManyToOne(targetEntity="DDC448ConnectedClass",  cascade={"all"}, fetch="EAGER")
     * @JoinColumn(name="connectedClassId", referencedColumnName="id", onDelete="CASCADE", nullable=true)
     */
    private $connectedClassId;
}

/**
 * @Entity
 * @Table(name="connectedClass")
 * @HasLifecycleCallbacks
 */
class DDC448ConnectedClass
{
    /**
     * @Id
     * @Column(name="id", type="integer")
     * @GeneratedValue(strategy="AUTO")
     */
    protected $id; // connected with DDC448MainTable
}

/**
 * @Entity
 * @Table(name="SubTable")
 */
class DDC448SubTable extends DDC448MainTable
{
}
