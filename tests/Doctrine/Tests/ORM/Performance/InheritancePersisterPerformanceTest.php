<?php

namespace Doctrine\Tests\ORM\Performance;

use Doctrine\ORM\Query;
use Doctrine\Tests\Models\Company\CompanyContract;
use Doctrine\Tests\Models\Company\CompanyEmployee;
use Doctrine\Tests\Models\Company\CompanyFixContract;
use Doctrine\Tests\Models\Company\CompanyFlexContract;
use Doctrine\Tests\Models\Company\CompanyFlexUltraContract;
use Doctrine\Tests\OrmFunctionalTestCase;

/**
* @group performance
 */
class InheritancePersisterPerformanceTest extends OrmFunctionalTestCase
{
    protected function setUp()
    {
        $this->useModelSet('company');
        parent::setUp();
    }

    public function testCompanyContract()
    {
        $person = new CompanyEmployee();
        $person->setName('Poor Sales Guy');
        $person->setDepartment('Sales');
        $person->setSalary(100);
        $this->_em->persist($person);

        for ($i = 0; $i < 33; $i++) {
            $fix = new CompanyFixContract();
            $fix->setFixPrice(1000);
            $fix->setSalesPerson($person);
            $fix->markCompleted();
            $this->_em->persist($fix);

            $flex = new CompanyFlexContract();
            $flex->setSalesPerson($person);
            $flex->setHoursWorked(100);
            $flex->setPricePerHour(100);
            $flex->markCompleted();
            $this->_em->persist($flex);

            $ultra = new CompanyFlexUltraContract();
            $ultra->setSalesPerson($person);
            $ultra->setHoursWorked(150);
            $ultra->setPricePerHour(150);
            $ultra->setMaxPrice(7000);
            $this->_em->persist($ultra);
        }

        $this->_em->flush();
        $this->_em->clear();

        $start = microtime(true);
        $contracts = $this->_em->getRepository(CompanyContract::class)->findAll();
        echo "99 CompanyContract: " . number_format(microtime(true) - $start, 6) . "\n";
        self::assertEquals(99, count($contracts));

        $this->_em->clear();

        $start = microtime(true);
        $contracts = $this->_em->getRepository(CompanyContract::class)->findAll();
        echo "99 CompanyContract: " . number_format(microtime(true) - $start, 6) . "\n";
        self::assertEquals(99, count($contracts));
    }
}
