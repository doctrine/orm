<?php

namespace Doctrine\Tests\Models\Company;

/**
 * @DoctrineEntity
 * @DoctrineTable(name="company_employees")
 * @DoctrineDiscriminatorValue("employee")
 * @DoctrineSubClasses({"Doctrine\Tests\Models\Company\CompanyManager"})
 */
class CompanyEmployee extends CompanyPerson
{
    /**
     * @DoctrineColumn(type="integer")
     */
    private $salary;

    /**
     * @DoctrineColumn(type="string", length=255)
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