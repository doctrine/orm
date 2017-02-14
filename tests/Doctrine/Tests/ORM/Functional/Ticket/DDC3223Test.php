<?php

namespace Doctrine\Tests\ORM\Functional\Ticket;

use Doctrine\ORM\Annotation as ORM;
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

        $this->em->persist($profileStatus);
        $this->em->persist($participant);
        $this->em->flush();
        $this->em->clear();

        $participant = $this->em->find(Participant::class, $participant->id);

        $profileStatus = clone $participant->profileStatus;

        self::assertSame(1, $profileStatus->getId(), 'The identifier on the cloned instance is an integer');
    }
}

/** @ORM\Entity @ORM\Table(name="ddc3223_journalist") */
class Journalist extends Participant
{
}

/**
 * @ORM\Entity @ORM\Table(name="ddc3223_participant")
 * @ORM\InheritanceType("JOINED")
 * @ORM\DiscriminatorColumn(name="discr", type="string")
 * @ORM\DiscriminatorMap({
 *     "journalist"  = "Journalist",
 *     "participant" = "Participant",
 * })
 */
class Participant
{
    /** @ORM\Id @ORM\Column(type="integer") @ORM\GeneratedValue */
    public $id;

    /** @ORM\ManyToOne(targetEntity="ProfileStatus") */
    public $profileStatus;
}

/**
 * @ORM\Entity @ORM\Table(name="ddc3223_status")
 * @ORM\InheritanceType("SINGLE_TABLE")
 * @ORM\DiscriminatorColumn(name="discr", type="string")
 * @ORM\DiscriminatorMap({
 *     "profile" = "ProfileStatus",
 *     "status"  = "Status",
 * })
 */
class Status
{
    /** @ORM\Id @ORM\Column(type="integer") @ORM\GeneratedValue(strategy="AUTO") */
    private $id;

    public function getId()
    {
        return $this->id;
    }
}

/**
 * @ORM\Entity
 */
class ProfileStatus extends Status
{
}
