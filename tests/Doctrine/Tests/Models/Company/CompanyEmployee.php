<?php

namespace Doctrine\Tests\Models\Company;

/**
 * @DoctrineEntity
 * @DoctrineTable(name="company_employee")
 * @DoctrineInheritanceType("joined")
 * @DoctrineDiscriminatorColumn(name="dtype", type="string", length=20)
 * @DoctrineDiscriminatorMap({
        "emp" = "Doctrine\Tests\Models\Company\CompanyEmployee",
        "man" = "Doctrine\Tests\Models\Company\CompanyManager"})
 * @DoctrineSubclasses({"Doctrine\Tests\Models\Company\CompanyManager"})
 */
class CompanyEmployee
{
    /**
     * @DoctrineId
     * @DoctrineColumn(type="integer")
     * @DoctrineGeneratedValue(strategy="auto")
     */
    public $id;

    /**
     * @DoctrineColumn(type="double")
     */
    public $salary;

    /**
     * @DoctrineColumn(type="string", length=255)
     */
    public $department;
}