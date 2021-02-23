<?php

namespace Doctrine\Tests\ORM\Functional\Ticket;

use Doctrine\Tests\OrmFunctionalTestCase;

class DDC3691Test extends OrmFunctionalTestCase {

    protected function setUp() {
        parent::setUp();

        $this->_schemaTool->createSchema(
            array(
                $this->_em->getClassMetadata(__NAMESPACE__ . '\DDC3691User')
            )
        );
    }

    public function testIssue() {
        $date = \DateTime::createFromFormat("!Y-m-d", "2015-03-01");

        $user = new DDC3691User();
        $user->setLastLoggedIn($date);

        $this->_em->persist($user);
        $this->_em->flush();

        $queryBuilder = $this->_em->createQueryBuilder()
            ->select("u")
            ->from(__NAMESPACE__ . "\\DDC3691User", "u");

        $result = $queryBuilder->getQuery()->execute();

        $this->assertCount(1, $result);
        /* @var $retrievedUser DDC3691User */
        $retrievedUser = $result[0];
        $this->assertEquals($date, $retrievedUser->getLastLoggedIn());

        $queryBuilder
            ->where("u.lastLoggedIn = :date")
            ->setParameter("date", $date);

        $result = $queryBuilder->getQuery()->execute();

        $this->assertCount(1, $result);
    }
}

/** @Entity @Table(name="users") */
class DDC3691User {
    /**
     * @Id @Column(type="integer")
     * @GeneratedValue(strategy="AUTO")
     */
    private $id;

    /** @Column(type="date") */
    private $lastLoggedIn;

    public function getId()
    {
        return $this->id;
    }

    public function setLastLoggedIn(\DateTime $datetime) {
        $this->lastLoggedIn = $datetime;
    }

    public function getLastLoggedIn() {
        return $this->lastLoggedIn;
    }
}
