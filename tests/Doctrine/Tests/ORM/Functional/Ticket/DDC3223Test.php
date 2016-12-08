<?php

namespace Doctrine\Tests\ORM\Functional\Ticket;

use Doctrine\Tests\OrmFunctionalTestCase;

/**
 * Functional tests for get Id after clone child entity
 *
 * @author Lallement Thomas <thomas.lallement@9online.fr>
 */
class DDC3223Test extends OrmFunctionalTestCase
{
    protected function setUp()
    {
        parent::setUp();

        $this->setUpEntitySchema(
            [
            Journalist::class,
            Participant::class,
            Status::class,
            ProfileStatus::class,
            ]
        );
    }

    public function testIssueGetId()
    {
        $profileStatus = new ProfileStatus();

        $participant = new Journalist();
        $participant->profileStatus = $profileStatus;

        $this->_em->persist($profileStatus);
        $this->_em->persist($participant);
        $this->_em->flush();
        $this->_em->clear();

        $participant = $this->_em->find(Participant::class, $participant->id);

        $profileStatus = clone $participant->profileStatus;

        $this->assertSame(1, $profileStatus->getId(), 'The identifier on the cloned instance is an integer');
    }
}

/** @Entity @Table(name="ddc3223_journalist") */
class Journalist extends Participant
{
}

/**
 * @Entity @Table(name="ddc3223_participant")
 * @InheritanceType("JOINED")
 * @DiscriminatorColumn(name="discr", type="string")
 * @DiscriminatorMap({
 *     "journalist"  = "Journalist",
 *     "participant" = "Participant",
 * })
 */
class Participant
{
    /** @Id @Column(type="integer") @GeneratedValue */
    public $id;

    /** @ManyToOne(targetEntity="ProfileStatus") */
    public $profileStatus;
}

/**
 * @Entity @Table(name="ddc3223_status")
 * @InheritanceType("SINGLE_TABLE")
 * @DiscriminatorColumn(name="discr", type="string")
 * @DiscriminatorMap({
 *     "profile" = "ProfileStatus",
 *     "status"  = "Status",
 * })
 */
class Status
{
    /** @Id @Column(type="integer") @GeneratedValue(strategy="AUTO") */
    private $id;

    public function getId()
    {
        return $this->id;
    }
}

/**
 * @Entity
 */
class ProfileStatus extends Status
{
}
