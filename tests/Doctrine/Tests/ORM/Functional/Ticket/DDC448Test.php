<?php
namespace Doctrine\Tests\ORM\Functional\Ticket;

require_once __DIR__ . '/../../../TestInit.php';

class DDC448Test extends \Doctrine\Tests\OrmFunctionalTestCase
{
    protected function setUp()
    {
        parent::setUp();
        $this->_schemaTool->createSchema(array(
            $this->_em->getClassMetadata(__NAMESPACE__ . '\DDC448MainTable'),
            $this->_em->getClassMetadata(__NAMESPACE__ . '\DDC448ConnectedClass'),
            $this->_em->getClassMetadata(__NAMESPACE__ . '\DDC448SubTable'),
        ));
    }

    public function testIssue()
    {
        $q = $this->_em->createQuery("select b from ".__NAMESPACE__."\\DDC448SubTable b where b.connectedClassId = ?1");
        $this->assertEquals('SELECT d0_.id AS id0, d0_.discr AS discr1, d0_.connectedClassId AS connectedClassId2 FROM SubTable s1_ INNER JOIN DDC448MainTable d0_ ON s1_.id = d0_.id WHERE d0_.connectedClassId = ?', $q->getSQL());
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
     * @JoinColumn(name="connectedClassId", referencedColumnName="id", onDelete="CASCADE", onUpdate="CASCADE", nullable=true)
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
