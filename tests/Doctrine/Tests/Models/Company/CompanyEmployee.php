<?php

namespace Doctrine\Tests\Models\Company;

use Doctrine\ORM\Annotation as ORM;

/**
 * @ORM\Entity
 * @ORM\Table(name="company_employees")
 */
class CompanyEmployee extends CompanyPerson
{
    /**
     * @ORM\Column(type="integer")
     */
    private $salary;

    /**
     * @ORM\Column(type="string", length=255)
     */
    private $department;

    /**
     * @ORM\Column(type="datetime", nullable=true)
     */
    private $startDate;

    /**
     * @ORM\ManyToMany(targetEntity="CompanyContract", mappedBy="engineers", fetch="EXTRA_LAZY")
     */
    public $contracts;

    /**
     * @ORM\OneToMany(targetEntity="CompanyFlexUltraContract", mappedBy="salesPerson", fetch="EXTRA_LAZY")
     */
    public $soldContracts;

    public function getSalary() {
        return $this->salary;
    }

    public function setSalary($salary) {
        $this->salary = $salary;
    }

    public function getDepartment() {
        return $this->department;
    }

    public function setDepartment($dep) {
        $this->department = $dep;
    }

    public function getStartDate() {
        return $this->startDate;
    }

    public function setStartDate($date) {
        $this->startDate = $date;
    }
}
