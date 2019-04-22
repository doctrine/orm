<?php

declare(strict_types=1);

namespace Doctrine\Tests\ORM\Functional\Ticket;

use Doctrine\ORM\Annotation as ORM;
use Doctrine\Tests\OrmFunctionalTestCase;
use ProxyManager\Proxy\GhostObjectInterface;

/**
 * Functional tests for get Id after clone child entity
 */
class DDC3223Test extends OrmFunctionalTestCase
{
    protected function setUp() : void
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

    public function testIssueGetId() : void
    {
        $profileStatus              = new ProfileStatus();
        $participant                = new Journalist();
        $participant->profileStatus = $profileStatus;

        $this->em->persist($profileStatus);
        $this->em->persist($participant);
        $this->em->flush();
        $this->em->clear();

        /** @var Participant $fetchedParticipant */
        $fetchedParticipant = $this->em->find(Participant::class, $participant->id);

        /** @var GhostObjectInterface|ProfileStatus $clonedProfileStatus */
        $clonedProfileStatus = clone $fetchedParticipant->profileStatus;

        self::assertInstanceOf(GhostObjectInterface::class, $clonedProfileStatus);
        self::assertInstanceOf(ProfileStatus::class, $clonedProfileStatus);
        self::assertTrue($clonedProfileStatus->isProxyInitialized());

        $clonedIdentifier = $clonedProfileStatus->getId();

        self::assertIsInt($clonedIdentifier);
        self::assertSame(
            $profileStatus->getId(),
            $clonedIdentifier,
            'The identifier on the cloned instance is an integer'
        );
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
 *     "journalist"  = Journalist::class,
 *     "participant" = Participant::class,
 * })
 */
class Participant
{
    /** @ORM\Id @ORM\Column(type="integer") @ORM\GeneratedValue */
    public $id;

    /**
     * @ORM\ManyToOne(targetEntity=ProfileStatus::class)
     * @ORM\JoinColumn(name="status_id", nullable=false)
     *
     * @var ProfileStatus
     */
    public $profileStatus;
}

/**
 * @ORM\Entity @ORM\Table(name="ddc3223_status")
 * @ORM\InheritanceType("SINGLE_TABLE")
 * @ORM\DiscriminatorColumn(name="discr", type="string")
 * @ORM\DiscriminatorMap({
 *     "profile" = ProfileStatus::class,
 *     "status"  = Status::class,
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
