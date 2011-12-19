<?php

namespace Doctrine\Tests\ORM\Functional\Ticket;

use DateTime;

require_once __DIR__ . '/../../../TestInit.php';

class DDC633Test extends \Doctrine\Tests\OrmFunctionalTestCase
{
    protected function setUp()
    {
        parent::setUp();
        try {
            $this->_schemaTool->createSchema(array(
                $this->_em->getClassMetadata(__NAMESPACE__ . '\DDC633Patient'),
                $this->_em->getClassMetadata(__NAMESPACE__ . '\DDC633Appointment'),
            ));
        } catch(\Exception $e) {

        }
    }

    /**
     * @group DDC-633
     * @group DDC-952
     * @group DDC-914
     */
    public function testOneToOneEager()
    {
        $app = new DDC633Appointment();
        $pat = new DDC633Patient();
        $app->patient = $pat;
        $pat->appointment = $app;

        $this->_em->persist($app);
        $this->_em->persist($pat);
        $this->_em->flush();
        $this->_em->clear();

        $eagerAppointment = $this->_em->find(__NAMESPACE__ . '\DDC633Appointment', $app->id);

        // Eager loading of one to one leads to fetch-join
        $this->assertNotInstanceOf('Doctrine\ORM\Proxy\Proxy', $eagerAppointment->patient);
        $this->assertTrue($this->_em->contains($eagerAppointment->patient));
    }

    /**
     * @group DDC-633
     * @group DDC-952
     */
    public function testDQLDeferredEagerLoad()
    {
        for ($i = 0; $i < 10; $i++) {
            $app = new DDC633Appointment();
            $pat = new DDC633Patient();
            $app->patient = $pat;
            $pat->appointment = $app;

            $this->_em->persist($app);
            $this->_em->persist($pat);
        }
        $this->_em->flush();
        $this->_em->clear();

        $appointments = $this->_em->createQuery("SELECT a FROM " . __NAMESPACE__ . "\DDC633Appointment a")->getResult();

        foreach ($appointments AS $eagerAppointment) {
            $this->assertInstanceOf('Doctrine\ORM\Proxy\Proxy', $eagerAppointment->patient);
            $this->assertTrue($eagerAppointment->patient->__isInitialized__, "Proxy should already be initialized due to eager loading!");
        }
    }
}

/**
 * @Entity
 */
class DDC633Appointment
{
    /** @Id @Column(type="integer") @GeneratedValue */
    public $id;

    /**
     * @OneToOne(targetEntity="DDC633Patient", inversedBy="appointment", fetch="EAGER")
     */
    public $patient;

}

/**
 * @Entity
 */
class DDC633Patient
{
    /** @Id @Column(type="integer") @GeneratedValue */
    public $id;

    /**
     * @OneToOne(targetEntity="DDC633Appointment", mappedBy="patient")
     */
    public $appointment;
}
