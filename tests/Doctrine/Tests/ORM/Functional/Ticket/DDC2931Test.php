<?php

namespace Doctrine\Tests\ORM\Functional\Ticket;

use Doctrine\ORM\Annotation as ORM;
use Doctrine\ORM\Query;

/**
 * @group DDC-2931
 */
class DDC2931Test extends \Doctrine\Tests\OrmFunctionalTestCase
{
    public function setUp()
    {
        parent::setUp();

        try {
            $this->schemaTool->createSchema(
                [
                $this->em->getClassMetadata(DDC2931User::class),
                ]
            );
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

        $this->em->persist($first);
        $this->em->persist($second);
        $this->em->persist($third);

        $this->em->flush();
        $this->em->clear();

        $second = $this->em->find(DDC2931User::class, $second->id);

        self::assertSame(2, $second->getRank());
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

        $this->em->persist($first);
        $this->em->persist($second);
        $this->em->persist($third);

        $this->em->flush();

        $first->value  = 4;
        $second->value = 5;
        $third->value  = 6;

        $refreshedSecond = $this
            ->em
            ->createQuery(
                'SELECT e, p, c FROM '
                . __NAMESPACE__ . '\\DDC2931User e LEFT JOIN e.parent p LEFT JOIN e.child c WHERE e = :id'
            )
            ->setParameter('id', $second)
            ->setHint(Query::HINT_REFRESH, true)
            ->getResult();

        self::assertCount(1, $refreshedSecond);
        self::assertSame(1, $first->value);
        self::assertSame(2, $second->value);
        self::assertSame(3, $third->value);
    }
}


/** @ORM\Entity */
class DDC2931User
{

    /** @ORM\Id @ORM\Column(type="integer") @ORM\GeneratedValue(strategy="AUTO") */
    public $id;

    /** @ORM\OneToOne(targetEntity="DDC2931User", inversedBy="child") */
    public $parent;

    /** @ORM\OneToOne(targetEntity="DDC2931User", mappedBy="parent") */
    public $child;

    /** @ORM\Column(type="integer") */
    public $value = 0;

    /**
     * Return Rank recursively
     * My rank is 1 + rank of my parent
     * @return int
     */
    public function getRank()
    {
        return 1 + ($this->parent ? $this->parent->getRank() : 0);
    }

    public function __wakeup()
    {
    }
}
