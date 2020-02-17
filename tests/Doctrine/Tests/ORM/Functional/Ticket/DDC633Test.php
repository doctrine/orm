<?php

declare(strict_types=1);

namespace Doctrine\Tests\ORM\Functional\Ticket;

use Doctrine\ORM\Annotation as ORM;
use Doctrine\Tests\OrmFunctionalTestCase;
use Exception;
use ProxyManager\Proxy\GhostObjectInterface;

class DDC633Test extends OrmFunctionalTestCase
{
    protected function setUp() : void
    {
        parent::setUp();
        try {
            $this->schemaTool->createSchema(
                [
                    $this->em->getClassMetadata(DDC633Patient::class),
                    $this->em->getClassMetadata(DDC633Appointment::class),
                ]
            );
        } catch (Exception $e) {
        }
    }

    /**
     * @group DDC-633
     * @group DDC-952
     * @group DDC-914
     */
    public function testOneToOneEager() : void
    {
        $app              = new DDC633Appointment();
        $pat              = new DDC633Patient();
        $app->patient     = $pat;
        $pat->appointment = $app;

        $this->em->persist($app);
        $this->em->persist($pat);
        $this->em->flush();
        $this->em->clear();

        $eagerAppointment = $this->em->find(DDC633Appointment::class, $app->id);

        // Eager loading of one to one leads to fetch-join
        self::assertNotInstanceOf(GhostObjectInterface::class, $eagerAppointment->patient);
        self::assertTrue($this->em->contains($eagerAppointment->patient));
    }

    /**
     * @group DDC-633
     * @group DDC-952
     */
    public function testDQLDeferredEagerLoad() : void
    {
        for ($i = 0; $i < 10; $i++) {
            $app              = new DDC633Appointment();
            $pat              = new DDC633Patient();
            $app->patient     = $pat;
            $pat->appointment = $app;

            $this->em->persist($app);
            $this->em->persist($pat);
        }
        $this->em->flush();
        $this->em->clear();

        $appointments = $this->em->createQuery('SELECT a FROM ' . __NAMESPACE__ . '\DDC633Appointment a')->getResult();

        foreach ($appointments as $eagerAppointment) {
            self::assertInstanceOf(GhostObjectInterface::class, $eagerAppointment->patient);
            self::assertTrue($eagerAppointment->patient->isProxyInitialized(), 'Proxy should already be initialized due to eager loading!');
        }
    }
}

/**
 * @ORM\Entity
 */
class DDC633Appointment
{
    /** @ORM\Id @ORM\Column(type="integer") @ORM\GeneratedValue */
    public $id;

    /** @ORM\OneToOne(targetEntity=DDC633Patient::class, inversedBy="appointment", fetch="EAGER") */
    public $patient;
}

/**
 * @ORM\Entity
 */
class DDC633Patient
{
    /** @ORM\Id @ORM\Column(type="integer") @ORM\GeneratedValue */
    public $id;

    /** @ORM\OneToOne(targetEntity=DDC633Appointment::class, mappedBy="patient") */
    public $appointment;
}
