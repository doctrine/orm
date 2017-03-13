<?php

namespace Doctrine\Tests\ORM\Functional\Ticket;

use Doctrine\ORM\Annotation as ORM;
use Doctrine\Tests\OrmFunctionalTestCase;

class DDC448Test extends OrmFunctionalTestCase
{
    protected function setUp()
    {
        parent::setUp();
        $this->schemaTool->createSchema(
            [
            $this->em->getClassMetadata(DDC448MainTable::class),
            $this->em->getClassMetadata(DDC448ConnectedClass::class),
            $this->em->getClassMetadata(DDC448SubTable::class),
            ]
        );
    }

    public function testIssue()
    {
        $q = $this->em->createQuery("select b from ".__NAMESPACE__."\\DDC448SubTable b where b.connectedClassId = ?1");

        self::assertSQLEquals(
            'SELECT t0."id" AS c0, t0."discr" AS c1, t0."connectedClassId" AS c2 FROM "SubTable" t1 INNER JOIN "DDC448MainTable" t0 ON t1."id" = t0."id" WHERE t0."connectedClassId" = ?',
            $q->getSQL()
        );
    }
}

/**
 * @ORM\Entity
 * @ORM\InheritanceType("JOINED")
 * @ORM\DiscriminatorColumn(name="discr", type="smallint")
 * @ORM\DiscriminatorMap({
 *     "0" = "DDC448MainTable",
 *     "1" = "DDC448SubTable"
 * })
 */
class DDC448MainTable
{
    /**
     * @ORM\Id
     * @ORM\Column(name="id", type="integer")
     * @ORM\GeneratedValue(strategy="AUTO")
     */
    private $id;

    /**
     * @ORM\ManyToOne(targetEntity="DDC448ConnectedClass",  cascade={"all"}, fetch="EAGER")
     * @ORM\JoinColumn(name="connectedClassId", referencedColumnName="id", onDelete="CASCADE", nullable=true)
     */
    private $connectedClassId;
}

/**
 * @ORM\Entity
 * @ORM\Table(name="connectedClass")
 * @ORM\HasLifecycleCallbacks
 */
class DDC448ConnectedClass
{
    /**
     * @ORM\Id
     * @ORM\Column(name="id", type="integer")
     * @ORM\GeneratedValue(strategy="AUTO")
     */
    protected $id; // connected with DDC448MainTable
}

/**
 * @ORM\Entity
 * @ORM\Table(name="SubTable")
 */
class DDC448SubTable extends DDC448MainTable
{
}
