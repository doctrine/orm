<?php

namespace Doctrine\Performance\Hydration;

use Doctrine\ORM\EntityManagerInterface;
use Doctrine\Performance\EntityManagerFactory;
use Doctrine\Tests\Models\Company;
use PhpBench\Benchmark\Metadata\Annotations\BeforeMethods;

/**
 * @BeforeMethods({"init"})
 */
final class SingleTableInheritanceInsertPerformanceBench
{
    /**
     * @var EntityManagerInterface
     */
    private $entityManager;

    /**
     * @var Company\CompanyFixContract[]
     */
    private $fixContracts = [];

    /**
     * @var Company\CompanyFlexContract[]
     */
    private $flexContracts = [];

    /**
     * @var Company\CompanyFlexUltraContract[]
     */
    private $ultraContracts = [];

    public function init()
    {
        $this->entityManager = EntityManagerFactory::getEntityManager([
            Company\CompanyPerson::class,
            Company\CompanyEmployee::class,
            Company\CompanyManager::class,
            Company\CompanyOrganization::class,
            Company\CompanyEvent::class,
            Company\CompanyAuction::class,
            Company\CompanyRaffle::class,
            Company\CompanyCar::class,
            Company\CompanyContract::class,
        ]);

        $person = new Company\CompanyEmployee();
        $person->setName('Poor Sales Guy');
        $person->setDepartment('Sales');
        $person->setSalary(100);
        $this->entityManager->persist($person);

        for ($i = 0; $i < 33; $i++) {
            $this->fixContracts[$i] = new Company\CompanyFixContract();
            $this->fixContracts[$i]->setFixPrice(1000);
            $this->fixContracts[$i]->setSalesPerson($person);
            $this->fixContracts[$i]->markCompleted();

            $this->flexContracts[$i] = new Company\CompanyFlexContract();
            $this->flexContracts[$i]->setSalesPerson($person);
            $this->flexContracts[$i]->setHoursWorked(100);
            $this->flexContracts[$i]->setPricePerHour(100);
            $this->flexContracts[$i]->markCompleted();

            $this->ultraContracts[$i] = new Company\CompanyFlexUltraContract();
            $this->ultraContracts[$i]->setSalesPerson($person);
            $this->ultraContracts[$i]->setHoursWorked(150);
            $this->ultraContracts[$i]->setPricePerHour(150);
            $this->ultraContracts[$i]->setMaxPrice(7000);
        }
    }

    public function benchInsertFixContracts()
    {
        array_map([$this->entityManager, 'persist'], $this->fixContracts);
        $this->entityManager->flush();
    }

    public function benchInsertFlexContracts()
    {
        array_map([$this->entityManager, 'persist'], $this->flexContracts);
        $this->entityManager->flush();
    }

    public function benchInsertUltraContracts()
    {
        array_map([$this->entityManager, 'persist'], $this->ultraContracts);
        $this->entityManager->flush();
    }

    public function benchInsertAllContracts()
    {
        array_map([$this->entityManager, 'persist'], $this->fixContracts);
        array_map([$this->entityManager, 'persist'], $this->flexContracts);
        array_map([$this->entityManager, 'persist'], $this->ultraContracts);
        $this->entityManager->flush();
    }
}
