<?php

namespace Doctrine\Tests\ORM\Performance;

use Doctrine\ORM\Tools\SchemaTool;
use Doctrine\ORM\Query;

require_once __DIR__ . '/../../TestInit.php';

/**
* @group performance
 */
class InheritancePersisterPerformanceTest extends \Doctrine\Tests\OrmFunctionalTestCase
{
    protected function setUp()
    {
        $this->useModelSet('company');
        parent::setUp();
    }

    public function testCompanyContract()
    {
        $person = new \Doctrine\Tests\Models\Company\CompanyEmployee();
        $person->setName('Poor Sales Guy');
        $person->setDepartment('Sales');
        $person->setSalary(100);
        $this->_em->persist($person);

        for ($i = 0; $i < 33; $i++) {
            $fix = new \Doctrine\Tests\Models\Company\CompanyFixContract();
            $fix->setFixPrice(1000);
            $fix->setSalesPerson($person);
            $fix->markCompleted();
            $this->_em->persist($fix);

            $flex = new \Doctrine\Tests\Models\Company\CompanyFlexContract();
            $flex->setSalesPerson($person);
            $flex->setHoursWorked(100);
            $flex->setPricePerHour(100);
            $flex->markCompleted();
            $this->_em->persist($flex);

            $ultra = new \Doctrine\Tests\Models\Company\CompanyFlexUltraContract();
            $ultra->setSalesPerson($person);
            $ultra->setHoursWorked(150);
            $ultra->setPricePerHour(150);
            $ultra->setMaxPrice(7000);
            $this->_em->persist($ultra);
        }

        $this->_em->flush();
        $this->_em->clear();

        $start = microtime(true);
        $contracts = $this->_em->getRepository('Doctrine\Tests\Models\Company\CompanyContract')->findAll();
        echo "99 CompanyContract: " . number_format(microtime(true) - $start, 6) . "\n";
        $this->assertEquals(99, count($contracts));

        $this->_em->clear();

        $start = microtime(true);
        $contracts = $this->_em->getRepository('Doctrine\Tests\Models\Company\CompanyContract')->findAll();
        echo "99 CompanyContract: " . number_format(microtime(true) - $start, 6) . "\n";
        $this->assertEquals(99, count($contracts));
    }
}