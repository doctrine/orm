<?php

namespace Doctrine\Tests\ORM\Functional\Ticket;

use Doctrine\Tests\OrmFunctionalTestCase;

/**
 * @group DDC-2350
 */
class DDC2350Test extends OrmFunctionalTestCase
{
    protected function setUp()
    {
        parent::setUp();

        $this->_schemaTool->createSchema(array(
            $this->_em->getClassMetadata(__NAMESPACE__ . '\DDC2350User'),
            $this->_em->getClassMetadata(__NAMESPACE__ . '\DDC2350Bug'),
        ));
    }

    public function testEagerCollectionsAreOnlyRetrievedOnce()
    {
        $user = new DDC2350User();
        $bug1 = new DDC2350Bug();
        $bug1->user = $user;
        $bug2 = new DDC2350Bug();
        $bug2->user = $user;

        $this->_em->persist($user);
        $this->_em->persist($bug1);
        $this->_em->persist($bug2);
        $this->_em->flush();

        $this->_em->clear();

        $cnt = $this->getCurrentQueryCount();
        $user = $this->_em->find(__NAMESPACE__ . '\DDC2350User', $user->id);

        $this->assertEquals($cnt + 2, $this->getCurrentQueryCount());

        $this->assertEquals(2, count($user->reportedBugs));

        $this->assertEquals($cnt + 2, $this->getCurrentQueryCount());
    }
}

/**
 * @Entity
 */
class DDC2350User
{
    /** @Id @Column(type="integer") @GeneratedValue */
    public $id;
    /** @OneToMany(targetEntity="DDC2350Bug", mappedBy="user", fetch="EAGER") */
    public $reportedBugs;
}

/**
 * @Entity
 */
class DDC2350Bug
{
    /** @Id @Column(type="integer") @GeneratedValue */
    public $id;
    /** @ManyToOne(targetEntity="DDC2350User", inversedBy="reportedBugs") */
    public $user;
}
