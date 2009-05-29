<?php

namespace Doctrine\Tests\Models\Company;

/**
 * @Entity
 * @Table(name="company_employees")
 * @DiscriminatorValue("employee")
 * @SubClasses({"Doctrine\Tests\Models\Company\CompanyManager"})
 */
class CompanyEmployee extends CompanyPerson
{
    /**
     * @Column(type="integer")
     */
    private $salary;

    /**
     * @Column(type="string", length=255)
     */
    private $department;

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
}