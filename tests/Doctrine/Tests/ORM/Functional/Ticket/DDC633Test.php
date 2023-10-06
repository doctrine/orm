<?php

declare(strict_types=1);

namespace Doctrine\Tests\ORM\Functional\Ticket;

use Doctrine\ORM\Mapping\Column;
use Doctrine\ORM\Mapping\Entity;
use Doctrine\ORM\Mapping\GeneratedValue;
use Doctrine\ORM\Mapping\Id;
use Doctrine\ORM\Mapping\OneToOne;
use Doctrine\Tests\OrmFunctionalTestCase;
use PHPUnit\Framework\Attributes\Group;

class DDC633Test extends OrmFunctionalTestCase
{
    protected function setUp(): void
    {
        parent::setUp();

        $this->createSchemaForModels(
            DDC633Patient::class,
            DDC633Appointment::class,
        );
    }

    #[Group('DDC-633')]
    #[Group('DDC-952')]
    #[Group('DDC-914')]
    public function testOneToOneEager(): void
    {
        $app              = new DDC633Appointment();
        $pat              = new DDC633Patient();
        $app->patient     = $pat;
        $pat->appointment = $app;

        $this->_em->persist($app);
        $this->_em->persist($pat);
        $this->_em->flush();
        $this->_em->clear();

        $eagerAppointment = $this->_em->find(DDC633Appointment::class, $app->id);

        // Eager loading of one to one leads to fetch-join
        self::assertFalse($this->isUninitializedObject($eagerAppointment->patient));
        self::assertTrue($this->_em->contains($eagerAppointment->patient));
    }

    #[Group('DDC-633')]
    #[Group('DDC-952')]
    public function testDQLDeferredEagerLoad(): void
    {
        for ($i = 0; $i < 10; $i++) {
            $app              = new DDC633Appointment();
            $pat              = new DDC633Patient();
            $app->patient     = $pat;
            $pat->appointment = $app;

            $this->_em->persist($app);
            $this->_em->persist($pat);
        }

        $this->_em->flush();
        $this->_em->clear();

        $appointments = $this->_em->createQuery('SELECT a FROM ' . __NAMESPACE__ . '\DDC633Appointment a')->getResult();

        foreach ($appointments as $eagerAppointment) {
            self::assertFalse($this->isUninitializedObject($eagerAppointment->patient), 'Proxy should already be initialized due to eager loading!');
        }
    }
}

#[Entity]
class DDC633Appointment
{
    /** @var int */
    #[Id]
    #[Column(type: 'integer')]
    #[GeneratedValue]
    public $id;

    /** @var DDC633Patient */
    #[OneToOne(targetEntity: 'DDC633Patient', inversedBy: 'appointment', fetch: 'EAGER')]
    public $patient;
}

#[Entity]
class DDC633Patient
{
    /** @var int */
    #[Id]
    #[Column(type: 'integer')]
    #[GeneratedValue]
    public $id;

    /** @var DDC633Appointment */
    #[OneToOne(targetEntity: 'DDC633Appointment', mappedBy: 'patient')]
    public $appointment;
}
