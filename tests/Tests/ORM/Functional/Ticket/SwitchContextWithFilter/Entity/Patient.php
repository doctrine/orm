<?php

declare(strict_types=1);

namespace Doctrine\Tests\ORM\Functional\Ticket\SwitchContextWithFilter\Entity;

use Doctrine\Common\Collections\ArrayCollection;
use Doctrine\Common\Collections\Collection;
use Doctrine\ORM\Mapping as ORM;

/**
 * @ORM\Entity()
 */
class Patient
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
     * @ORM\Column(type="string")
     *
     * @var string
     */
    public $name;

    /**
     * @ORM\OneToMany(targetEntity=PatientInsurance::class, mappedBy="patient", fetch="LAZY", cascade={"persist"})
     *
     * @var Collection<int, PatientInsurance>
     */
    public $insurances;

    public function __construct(string $name)
    {
        $this->name       = $name;
        $this->insurances = new ArrayCollection();
    }

    /**
     * @return Collection<PrimaryPatInsurance>
     */
    public function getPrimaryInsurances(): Collection
    {
        return $this->insurances->filter(static function (PatientInsurance $insurances) {
            return $insurances instanceof PrimaryPatInsurance;
        });
    }

    /**
     * @return Collection<SecondaryPatInsurance>
     */
    public function getSecondaryInsurances(): Collection
    {
        return $this->insurances->filter(static function (PatientInsurance $insurances) {
            return $insurances instanceof SecondaryPatInsurance;
        });
    }

    public function addPrimaryInsurance(Insurance $insurance): void
    {
        $this->insurances[] = new PrimaryPatInsurance($this, $insurance);
    }

    public function addSecondaryInsurance(Insurance $insurance): void
    {
        $this->insurances[] = new SecondaryPatInsurance($this, $insurance);
    }
}
