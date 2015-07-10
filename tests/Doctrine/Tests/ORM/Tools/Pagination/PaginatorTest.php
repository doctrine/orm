<?php

namespace Doctrine\Tests\ORM\Tools\Pagination;

use Doctrine\ORM\Query;
use Doctrine\ORM\Tools\Pagination\Paginator;
use Doctrine\Tests\Models\Pagination\Company;
use Doctrine\Tests\Models\Pagination\Department;
use Doctrine\Tests\Models\Pagination\Logo;

class PaginatorTest extends \Doctrine\Tests\OrmFunctionalTestCase
{
    public function setUp()
    {
        $this->useModelSet('pagination');
        parent::setUp();
        $this->populate();

        // we don't want cache to break tests / hide bug.
        $this->_em->clear();
    }

    public function testSimpleUse()
    {
        // Test simple test
        $query = $this->_em->createQuery(
            'SELECT c, d FROM Doctrine\Tests\Models\Pagination\Company c LEFT JOIN c.departments d'
        );

        $results = $query->getResult();
        $this->assertEquals(9, count($results), 'Expecting nine rows to be returned');

        // reset
        unset($results);

        $paginator = new Paginator($query, true);
        $this->assertEquals(9, count($paginator), 'Expecting nine rows to count in Paginator');


        foreach ($paginator as $company) {
            $this->checkCompany($company);
        }
    }

    public function testLimitAndOffset()
    {
        // Test simple test
        $query = $this->_em->createQuery(
            'SELECT c, d FROM Doctrine\Tests\Models\Pagination\Company c LEFT JOIN c.departments d'
        )->setFirstResult(5)->setMaxResults(2);

        $paginator = new Paginator($query, true);
        $this->assertEquals(9, count($paginator), 'Expecting nine rows to count in Paginator');
        $this->assertEquals(9, $paginator->count(), 'Expecting nine rows to count in Paginator');

        $results = $paginator->getIterator()->getArrayCopy();
        $this->assertEquals(2, count($results), 'Expecting to retrieve two rows only');
        foreach ($results as $company) {
            $this->checkCompany($company);
        }

        // expect companies 'name5' and 'name6' to be returned
        $this->assertEquals('name5', $results[0]->name, 'first company retrieved should be "name5"');
        $this->assertEquals('name6', $results[1]->name, 'second company retrieved should be "name6"');
    }

    public function testWhereInMainObject()
    {
        $query = $this->_em->createQuery(
            'SELECT c, d FROM Doctrine\Tests\Models\Pagination\Company c LEFT JOIN c.departments d '.
            ' WHERE c.name = \'name0\''
        )->setFirstResult(0)->setMaxResults(1);

        $paginator = new Paginator($query, true);
        $this->assertEquals(1, count($paginator), 'Expecting one row to match in Paginator');

        $results = $paginator->getIterator()->getArrayCopy();
        $this->checkCompany($results[0]);

        $this->assertEquals('name0', $results[0]->name, 'expecting company returned to be named "name0"');
    }

    public function testDDC3818()
    {
        $query = $this->_em->createQuery(
            'SELECT c, d FROM Doctrine\Tests\Models\Pagination\Company c LEFT JOIN c.departments d '.
            ' WHERE d.name = \'namedep5-0\''
        )->setFirstResult(0)->setMaxResults(1);
        
        $paginator = new Paginator($query, true);
        $this->assertEquals(1, count($paginator), 'Expecting one row to match in Paginator');

        //xdebug_start_trace('/tmp/test');
        $results = $paginator->getIterator()->getArrayCopy();
        //xdebug_stop_trace();
        $this->checkCompany($results[0]);

        $this->assertEquals('name5', $results[0]->name, 'expecting company returned to be named "name0"');
        foreach ($results[0]->departments as $dep) {
            $this->assertContains('namedep5-', $dep->name);
        }

    }

    protected function checkCompany($company)
    {
        $this->assertTrue($company instanceof Company, 'result is a Company object');
        $this->assertTrue($company->logo instanceof Logo, 'logo is a Logo object');
        $this->assertEquals(3, count($company->departments), 'expect 3 departments per Company');
        foreach ($company->departments as $dep) {
            $this->assertTrue($dep instanceof Department, 'Department object expected');
        }
    }

    public function populate()
    {
        for ($i = 0; $i < 9; $i++) {
            $company = new Company();
            $company->name = "name$i";
            $company->logo = new Logo();
            $company->logo->image = "image$i";
            $company->logo->image_width = 100 + $i;
            $company->logo->image_height = 100 + $i;
            $company->logo->company = $company;
            for ($j=0; $j<3; $j++) {
                $department = new Department();
                $department->name = "namedep$i-$j";
                $department->company = $company;
                $company->departments[] = $department;
            }
            $this->_em->persist($company);
        }
        $this->_em->flush();
    }
}
