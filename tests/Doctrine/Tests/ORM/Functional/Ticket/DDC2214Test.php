<?php

namespace Doctrine\Tests\ORM\Functional\Ticket;

use Doctrine\ORM\Annotation as ORM;

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

        $this->schemaTool->createSchema(
            [
            $this->em->getClassMetadata(DDC2214Foo::class),
            $this->em->getClassMetadata(DDC2214Bar::class),
            ]
        );
    }

    public function testIssue()
    {
        $foo = new DDC2214Foo();
        $bar = new DDC2214Bar();

        $foo->bar = $bar;

        $this->em->persist($foo);
        $this->em->persist($bar);
        $this->em->flush();
        $this->em->clear();

        /* @var $foo \Doctrine\Tests\ORM\Functional\Ticket\DDC2214Foo */
        $foo = $this->em->find(DDC2214Foo::class, $foo->id);
        $bar = $foo->bar;

        $logger  = $this->em->getConnection()->getConfiguration()->getSQLLogger();

        $related = $this
            ->em
            ->createQuery('SELECT b FROM '.__NAMESPACE__ . '\DDC2214Bar b WHERE b.id IN(:ids)')
            ->setParameter('ids', [$bar])
            ->getResult();

        $query = end($logger->queries);

        self::assertEquals(\Doctrine\DBAL\Connection::PARAM_INT_ARRAY, $query['types'][0]);
    }
}

/** @ORM\Entity */
class DDC2214Foo
{
    /** @ORM\Id @ORM\Column(type="integer") @ORM\GeneratedValue(strategy="AUTO") */
    public $id;

    /** @ORM\ManyToOne(targetEntity="DDC2214Bar") */
    public $bar;
}

/** @ORM\Entity */
class DDC2214Bar
{
    /** @ORM\Id @ORM\Column(type="integer") @ORM\GeneratedValue(strategy="AUTO") */
    public $id;
}
