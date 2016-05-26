<?php

namespace Doctrine\Performance\Hydration;

use Doctrine\Common\Persistence\ObjectRepository;
use Doctrine\Performance\EntityManagerFactory;
use Doctrine\Tests\Models\Company;
use PhpBench\Benchmark\Metadata\Annotations\BeforeMethods;

/**
 * @BeforeMethods({"init"})
 */
final class SingleTableInheritanceHydrationPerformanceBench
{
    /**
     * @var ObjectRepository
     */
    private $contractsRepository;

    /**
     * @var ObjectRepository
     */
    private $fixContractsRepository;

    /**
     * @var ObjectRepository
     */
    private $flexContractRepository;

    /**
     * @var ObjectRepository
     */
    private $ultraContractRepository;

    public function init()
    {
        $entityManager = EntityManagerFactory::getEntityManager([
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

        $this->contractsRepository     = $entityManager->getRepository(Company\CompanyContract::class);
        $this->fixContractsRepository  = $entityManager->getRepository(Company\CompanyFixContract::class);
        $this->flexContractRepository  = $entityManager->getRepository(Company\CompanyFlexContract::class);
        $this->ultraContractRepository = $entityManager->getRepository(Company\CompanyFlexUltraContract::class);

        $person = new Company\CompanyEmployee();
        $person->setName('Poor Sales Guy');
        $person->setDepartment('Sales');
        $person->setSalary(100);
        $entityManager->persist($person);

        for ($i = 0; $i < 33; $i++) {
            $fixContract   = new Company\CompanyFixContract();
            $flexContract  = new Company\CompanyFlexContract();
            $ultraContract = new Company\CompanyFlexUltraContract();

            $fixContract->setFixPrice(1000);
            $fixContract->setSalesPerson($person);
            $fixContract->markCompleted();

            $flexContract->setSalesPerson($person);
            $flexContract->setHoursWorked(100);
            $flexContract->setPricePerHour(100);
            $flexContract->markCompleted();

            $ultraContract->setSalesPerson($person);
            $ultraContract->setHoursWorked(150);
            $ultraContract->setPricePerHour(150);
            $ultraContract->setMaxPrice(7000);

            $entityManager->persist($fixContract);
            $entityManager->persist($flexContract);
            $entityManager->persist($ultraContract);
        }

        $entityManager->flush();
        $entityManager->clear();
    }

    public function benchHydrateFixContracts()
    {
        $this->fixContractsRepository->findAll();
    }

    public function benchHydrateFlexContracts()
    {
        $this->flexContractRepository->findAll();
    }

    public function benchHydrateUltraContracts()
    {
        $this->ultraContractRepository->findAll();
    }

    public function benchHydrateAllContracts()
    {
        $this->contractsRepository->findAll();
    }
}
