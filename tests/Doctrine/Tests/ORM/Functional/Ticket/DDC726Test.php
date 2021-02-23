<?php

namespace Doctrine\Tests\ORM\Functional\Ticket;

class DDC726Test extends \Doctrine\Tests\OrmFunctionalTestCase
{
    protected function setUp()
    {
        parent::setUp();
        $this->_em->getConnection()->getConfiguration()->setSQLLogger(new \Doctrine\DBAL\Logging\EchoSQLLogger);
        $this->_schemaTool->createSchema(
            [
            $this->_em->getClassMetadata(DDC726Group::class),
            ]
        );
    }

    public function testWhereInWithCompositeIdentifiers()
    {
        $sql = <<<SQL
SELECT a FROM groups a
INNER JOIN groups_foo b ON b.id = a.foo
INNER JOIN groups_bar c ON c.id = a.bar
WHERE (b.id, c.id) IN (
    SELECT a2.foo, a2.bar
    FROM groups a2
    WHERE a2.name = 'foo'
)
SQL;
        $in = $this->_em->createQueryBuilder();
        $in->select('IDENTITY(d) FROM Doctrine\Tests\ORM\Functional\Ticket\DDC726Group d WHERE d.name = "foo"');

        $qb = $this->_em->createQueryBuilder();
        $qb->select('a')
           ->from(DDC726Group::class, 'a')
           ->innerJoin('a.foo', 'b')
           ->innerJoin('a.bar', 'c')
           ->where($qb->expr()->in('(b.id, c.id)', $in->getDQL()));

        $this->assertEquals($qb->getQuery()->getSQL(), $sql);
    }
}

/**
 * @Entity
 * @Table(name="groups_foo")
 */
class DDC726GroupFoo {
    /**
     * @Id @GeneratedValue
     * @Column(type="integer")
     */
    private $id;

    public function getId() { return $this->id; }
}

/**
 * @Entity
 * @Table(name="groups_bar")
 */
class DDC726GroupBar {
    /**
     * @Id @GeneratedValue
     * @Column(type="integer")
     */
    private $id;

    public function getId() { return $this->id; }
}

/**
 * @Entity
 * @Table(name="groups")
 */
class DDC726Group {
    /** @Column(type="string", nullable=false) */
    private $name;

    /**
     * @Id
     * @ManyToOne(targetEntity="DDC726GroupFoo", inversedBy="foo")
     * @JoinColumn(name="foo_id", referencedColumnName="id", nullable=false)
     */
    private $foo;

    /**
     * @Id
     * @ManyToOne(targetEntity="DDC726GroupBar", inversedBy="bar")
     * @JoinColumn(name="bar_id", referencedColumnName="id", nullable=false)
     */
    private $bar;

    public function getName() { return $this->name; }

    public function setName($name) { $this->name = $name; }

    public function getFoo() { return $this->foo; }

    public function setFoo(DDC726GroupFoo $foo) { $this->foo = $foo; }

    public function getBar() { return $this->bar; }

    public function setBar(DDC726GroupBar $bar) { $this->bar = $bar; }
}
