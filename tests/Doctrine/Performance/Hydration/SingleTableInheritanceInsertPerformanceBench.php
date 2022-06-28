<?php

declare(strict_types=1);

namespace Doctrine\Performance\Hydration;

use Doctrine\ORM\EntityManagerInterface;
use Doctrine\Performance\EntityManagerFactory;
use Doctrine\Tests\Models\Company;
use Doctrine\Tests\Models\Company\CompanyAuction;
use Doctrine\Tests\Models\Company\CompanyCar;
use Doctrine\Tests\Models\Company\CompanyContract;
use Doctrine\Tests\Models\Company\CompanyEmployee;
use Doctrine\Tests\Models\Company\CompanyEvent;
use Doctrine\Tests\Models\Company\CompanyFixContract;
use Doctrine\Tests\Models\Company\CompanyFlexContract;
use Doctrine\Tests\Models\Company\CompanyFlexUltraContract;
use Doctrine\Tests\Models\Company\CompanyManager;
use Doctrine\Tests\Models\Company\CompanyOrganization;
use Doctrine\Tests\Models\Company\CompanyPerson;
use Doctrine\Tests\Models\Company\CompanyRaffle;
use PhpBench\Benchmark\Metadata\Annotations\BeforeMethods;

use function array_map;

/** @BeforeMethods({"init"}) */
final class SingleTableInheritanceInsertPerformanceBench
{
    private EntityManagerInterface|null $entityManager = null;

    /** @var Company\CompanyFixContract[] */
    private array $fixContracts = [];

    /** @var Company\CompanyFlexContract[] */
    private array $flexContracts = [];

    /** @var Company\CompanyFlexUltraContract[] */
    private array $ultraContracts = [];

    public function init(): void
    {
        $this->entityManager = EntityManagerFactory::getEntityManager([
            CompanyPerson::class,
            CompanyEmployee::class,
            CompanyManager::class,
            CompanyOrganization::class,
            CompanyEvent::class,
            CompanyAuction::class,
            CompanyRaffle::class,
            CompanyCar::class,
            CompanyContract::class,
        ]);

        $person = new CompanyEmployee();
        $person->setName('Poor Sales Guy');
        $person->setDepartment('Sales');
        $person->setSalary(100);
        $this->entityManager->persist($person);

        for ($i = 0; $i < 33; $i++) {
            $this->fixContracts[$i] = new CompanyFixContract();
            $this->fixContracts[$i]->setFixPrice(1000);
            $this->fixContracts[$i]->setSalesPerson($person);
            $this->fixContracts[$i]->markCompleted();

            $this->flexContracts[$i] = new CompanyFlexContract();
            $this->flexContracts[$i]->setSalesPerson($person);
            $this->flexContracts[$i]->setHoursWorked(100);
            $this->flexContracts[$i]->setPricePerHour(100);
            $this->flexContracts[$i]->markCompleted();

            $this->ultraContracts[$i] = new CompanyFlexUltraContract();
            $this->ultraContracts[$i]->setSalesPerson($person);
            $this->ultraContracts[$i]->setHoursWorked(150);
            $this->ultraContracts[$i]->setPricePerHour(150);
            $this->ultraContracts[$i]->setMaxPrice(7000);
        }
    }

    public function benchInsertFixContracts(): void
    {
        array_map([$this->entityManager, 'persist'], $this->fixContracts);
        $this->entityManager->flush();
    }

    public function benchInsertFlexContracts(): void
    {
        array_map([$this->entityManager, 'persist'], $this->flexContracts);
        $this->entityManager->flush();
    }

    public function benchInsertUltraContracts(): void
    {
        array_map([$this->entityManager, 'persist'], $this->ultraContracts);
        $this->entityManager->flush();
    }

    public function benchInsertAllContracts(): void
    {
        array_map([$this->entityManager, 'persist'], $this->fixContracts);
        array_map([$this->entityManager, 'persist'], $this->flexContracts);
        array_map([$this->entityManager, 'persist'], $this->ultraContracts);
        $this->entityManager->flush();
    }
}
