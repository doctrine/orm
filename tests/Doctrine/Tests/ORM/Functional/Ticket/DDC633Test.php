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

        $this->assertNotType('Doctrine\ORM\Proxy\Proxy', $eagerAppointment->patient);
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