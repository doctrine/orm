<?php

namespace Doctrine\Tests\ORM\Functional\Ticket;

use Doctrine\ORM\Annotation as ORM;
use Doctrine\Tests\OrmFunctionalTestCase;

/**
 * @group DDC-2350
 * @group non-cacheable
 */
class DDC2350Test extends OrmFunctionalTestCase
{
    protected function setUp()
    {
        parent::setUp();

        $this->schemaTool->createSchema(
            [
            $this->em->getClassMetadata(DDC2350User::class),
            $this->em->getClassMetadata(DDC2350Bug::class),
            ]
        );
    }

    public function testEagerCollectionsAreOnlyRetrievedOnce()
    {
        $user = new DDC2350User();
        $bug1 = new DDC2350Bug();
        $bug1->user = $user;
        $bug2 = new DDC2350Bug();
        $bug2->user = $user;

        $this->em->persist($user);
        $this->em->persist($bug1);
        $this->em->persist($bug2);
        $this->em->flush();

        $this->em->clear();

        $cnt = $this->getCurrentQueryCount();
        $user = $this->em->find(DDC2350User::class, $user->id);

        self::assertEquals($cnt + 1, $this->getCurrentQueryCount());

        self::assertEquals(2, count($user->reportedBugs));

        self::assertEquals($cnt + 1, $this->getCurrentQueryCount());
    }
}

/**
 * @ORM\Entity
 */
class DDC2350User
{
    /** @ORM\Id @ORM\Column(type="integer") @ORM\GeneratedValue */
    public $id;
    /** @ORM\OneToMany(targetEntity="DDC2350Bug", mappedBy="user", fetch="EAGER") */
    public $reportedBugs;
}

/**
 * @ORM\Entity
 */
class DDC2350Bug
{
    /** @ORM\Id @ORM\Column(type="integer") @ORM\GeneratedValue */
    public $id;
    /** @ORM\ManyToOne(targetEntity="DDC2350User", inversedBy="reportedBugs") */
    public $user;
}
