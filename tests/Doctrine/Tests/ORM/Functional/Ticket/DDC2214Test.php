<?php

namespace Doctrine\Tests\ORM\Functional\Ticket;

/**
 * Verifies that the type of parameters being bound to an SQL query is the same
 * of the identifier of the entities used as parameters in the DQL query, even
 * if the bound objects are proxies.
 *
 * @author Marco Pivetta <ocramius@gmail.com>
 *
 * @group DDC-2214
 */
class DDC2214Test extends \Doctrine\Tests\OrmFunctionalTestCase
{
    protected function setUp()
    {
        parent::setUp();

        $this->_schemaTool->createSchema(array(
            $this->_em->getClassMetadata(__NAMESPACE__ . '\DDC2214Foo'),
            $this->_em->getClassMetadata(__NAMESPACE__ . '\DDC2214Bar'),
        ));
    }

    public function testIssue()
    {
        $foo = new DDC2214Foo();
        $bar = new DDC2214Bar();

        $foo->bar = $bar;

        $this->_em->persist($foo);
        $this->_em->persist($bar);
        $this->_em->flush();
        $this->_em->clear();

        /* @var $foo \Doctrine\Tests\ORM\Functional\Ticket\DDC2214Foo */
        $foo = $this->_em->find(__NAMESPACE__ . '\\DDC2214Foo', $foo->id);
        $bar = $foo->bar;

        $logger  = $this->_em->getConnection()->getConfiguration()->getSQLLogger();

        $related = $this
            ->_em
            ->createQuery('SELECT b FROM '.__NAMESPACE__ . '\DDC2214Bar b WHERE b.id IN(:ids)')
            ->setParameter('ids', array($bar))
            ->getResult();

        $query = end($logger->queries);

        $this->assertEquals(\Doctrine\DBAL\Connection::PARAM_INT_ARRAY, $query['types'][0]);
    }
}

/** @Entity */
class DDC2214Foo
{
    /** @Id @Column(type="integer") @GeneratedValue(strategy="AUTO") */
    public $id;

    /** @ManyToOne(targetEntity="DDC2214Bar") */
    public $bar;
}

/** @Entity */
class DDC2214Bar
{
    /** @Id @Column(type="integer") @GeneratedValue(strategy="AUTO") */
    public $id;
}
