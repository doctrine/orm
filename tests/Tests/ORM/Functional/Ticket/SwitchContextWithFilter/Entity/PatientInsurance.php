<?php

declare(strict_types=1);

namespace Doctrine\Tests\ORM\Functional\Ticket\SwitchContextWithFilter\Entity;

use Doctrine\ORM\Mapping as ORM;

/**
 * @ORM\Entity()
 * @ORM\InheritanceType("SINGLE_TABLE")
 * @ORM\DiscriminatorMap({"primary": PrimaryPatInsurance::class, "secondary": SecondaryPatInsurance::class})
 * @ORM\DiscriminatorColumn(name="type", type="string")
 */
abstract class PatientInsurance
{
    /**
     * @ORM\Id
     * @ORM\GeneratedValue
     * @ORM\Column(type="integer")
     *
     * @var int
     */
    public $id;

    /**
     * @ORM\ManyToOne(targetEntity=Insurance::class, fetch="EAGER", cascade={"persist"})
     * @ORM\JoinColumn(referencedColumnName="id", nullable=false)
     *
     * @var Insurance
     */
    public $insurance;

    /**
     * @ORM\ManyToOne(targetEntity=Patient::class, inversedBy="insurances")
     *
     * @var Patient
     */
    public $patient;

    public function __construct(Patient $patient, Insurance $insurance)
    {
        $this->patient   = $patient;
        $this->insurance = $insurance;
    }
}
