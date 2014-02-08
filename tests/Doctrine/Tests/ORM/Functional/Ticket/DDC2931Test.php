<?php

namespace Doctrine\Tests\ORM\Functional\Ticket;

use Doctrine\ORM\Query;

require_once __DIR__ . '/../../../TestInit.php';

/**
 * @group DDC-2931
 */
class DDC2931Test extends \Doctrine\Tests\OrmFunctionalTestCase
{
    public function setUp()
    {
        parent::setUp();

        try {
            $this->_schemaTool->createSchema(array(
                $this->_em->getClassMetadata(__NAMESPACE__ . '\DDC2931User'),
            ));
        } catch (\Exception $e) {
            // no action needed - schema seems to be already in place
        }
    }

    public function testIssue()
    {
        $first  = new DDC2931User();
        $second = new DDC2931User();
        $third  = new DDC2931User();

        $second->parent = $first;
        $third->parent  = $second;

        $this->_em->persist($first);
        $this->_em->persist($second);
        $this->_em->persist($third);

        $this->_em->flush();
        $this->_em->clear();

        $second = $this->_em->find('Doctrine\Tests\ORM\Functional\Ticket\DDC2931User', $second->id);

        $this->assertSame(2, $second->getRank());
    }

    public function testFetchJoinedEntitiesCanBeRefreshed()
    {
        $first  = new DDC2931User();
        $second = new DDC2931User();
        $third  = new DDC2931User();

        $second->parent = $first;
        $third->parent  = $second;

        $first->value  = 1;
        $second->value = 2;
        $third->value  = 3;

        $this->_em->persist($first);
        $this->_em->persist($second);
        $this->_em->persist($third);

        $this->_em->flush();

        $first->value  = 4;
        $second->value = 5;
        $third->value  = 6;

        $refreshedSecond = $this
            ->_em
            ->createQuery(
                'SELECT e, p, c FROM '
                . __NAMESPACE__ . '\\DDC2931User e LEFT JOIN e.parent p LEFT JOIN e.child c WHERE e = :id'
            )
            ->setParameter('id', $second)
            ->setHint(Query::HINT_REFRESH, true)
            ->getResult();

        $this->assertCount(1, $refreshedSecond);
        $this->assertSame(1, $first->value);
        $this->assertSame(2, $second->value);
        $this->assertSame(3, $third->value);
    }
}


/** @Entity */
class DDC2931User
{

    /** @Id @Column(type="integer") @GeneratedValue(strategy="AUTO") */
    public $id;

    /** @OneToOne(targetEntity="DDC2931User", inversedBy="child") */
    public $parent;

    /** @OneToOne(targetEntity="DDC2931User", mappedBy="parent") */
    public $child;

    /** @Column(type="integer") */
    public $value = 0;

    /**
     * Return Rank recursively
     * My rank is 1 + rank of my parent
     * @return integer
     */
    public function getRank()
    {
        return 1 + ($this->parent ? $this->parent->getRank() : 0);
    }

    public function __wakeup()
    {
        echo 'foo';
    }
}
