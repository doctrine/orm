<?php

declare(strict_types=1);

namespace Doctrine\Tests\ORM\Functional\Ticket;

use Doctrine\ORM\Mapping\Column;
use Doctrine\ORM\Mapping\Entity;
use Doctrine\ORM\Mapping\GeneratedValue;
use Doctrine\ORM\Mapping\Id;
use Doctrine\ORM\Mapping\JoinColumn;
use Doctrine\ORM\Mapping\OneToOne;
use Doctrine\ORM\Mapping\Table;
use Doctrine\ORM\UnitOfWork;
use Doctrine\Tests\OrmFunctionalTestCase;
use PHPUnit\Framework\Attributes\Group;

use function get_class;

#[Group('GH10808')]
class GH10808Test extends OrmFunctionalTestCase
{
    protected function setUp(): void
    {
        parent::setUp();

        $this->createSchemaForModels(
            GH10808Appointment::class,
            GH10808AppointmentChild::class,
        );
    }

    public function testDQLDeferredEagerLoad(): void
    {
        $appointment = new GH10808Appointment();

        $this->_em->persist($appointment);
        $this->_em->flush();
        $this->_em->clear();

        $query = $this->_em->createQuery(
            'SELECT appointment from Doctrine\Tests\ORM\Functional\Ticket\GH10808Appointment appointment
               JOIN appointment.child appointment_child',
        );

        // By default, UnitOfWork::HINT_DEFEREAGERLOAD is set to 'true'
        $deferredLoadResult = $query->getSingleResult();

        // Clear the EM to prevent the recovery of the loaded instance, which would otherwise result in a proxy.
        $this->_em->clear();

        $eagerLoadResult = $query->setHint(UnitOfWork::HINT_DEFEREAGERLOAD, false)->getSingleResult();

        self::assertNotEquals(
            GH10808AppointmentChild::class,
            get_class($deferredLoadResult->child),
            '$deferredLoadResult->child should be a proxy',
        );
        self::assertEquals(
            GH10808AppointmentChild::class,
            get_class($eagerLoadResult->child),
            '$eagerLoadResult->child should not be a proxy',
        );
    }
}

#[Entity]
#[Table(name: 'gh10808_appointment')]
class GH10808Appointment
{
    /** @var int */
    #[Id]
    #[Column(type: 'integer')]
    #[GeneratedValue]
    public $id;

    /** @var GH10808AppointmentChild */
    #[OneToOne(targetEntity: GH10808AppointmentChild::class, cascade: ['persist', 'remove'], fetch: 'EAGER')]
    #[JoinColumn(name: 'child_id', referencedColumnName: 'id')]
    public $child;

    public function __construct()
    {
        $this->child = new GH10808AppointmentChild();
    }
}

#[Entity]
#[Table(name: 'gh10808_appointment_child')]
class GH10808AppointmentChild
{
    /** @var int */
    #[Id]
    #[Column(type: 'integer')]
    #[GeneratedValue]
    private $id;
}
