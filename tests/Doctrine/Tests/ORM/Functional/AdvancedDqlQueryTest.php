<?php

namespace Doctrine\Tests\ORM\Functional;

use Doctrine\Tests\Models\Company\CompanyEmployee,
    Doctrine\Tests\Models\Company\CompanyManager,
    Doctrine\Tests\Models\Company\CompanyPerson,
    Doctrine\Tests\Models\Company\CompanyCar;

require_once __DIR__ . '/../../TestInit.php';

/**
 * Functional Query tests.
 *
 * @author Benjamin <kontakt@beberlei.de>
 */
class AdvancedDqlQueryTest extends \Doctrine\Tests\OrmFunctionalTestCase
{
    protected function setUp()
    {
        $this->useModelSet('company');
        parent::setUp();

        $this->generateFixture();
    }

    public function testAggregateWithHavingClause()
    {
        $dql = 'SELECT p.department, AVG(p.salary) AS avgSalary '.
               'FROM Doctrine\Tests\Models\Company\CompanyEmployee p '.
               'GROUP BY p.department HAVING SUM(p.salary) > 200000 ORDER BY p.department';

        $result = $this->_em->createQuery($dql)->getScalarResult();

        $this->assertEquals(2, count($result));
        $this->assertEquals('IT', $result[0]['department']);
        $this->assertEquals(150000, $result[0]['avgSalary']);
        $this->assertEquals('IT2', $result[1]['department']);
        $this->assertEquals(600000, $result[1]['avgSalary']);
    }

    public function testUnnamedScalarResultsAreOneBased()
    {
        $dql = 'SELECT p.department, AVG(p.salary) '.
               'FROM Doctrine\Tests\Models\Company\CompanyEmployee p '.
               'GROUP BY p.department HAVING SUM(p.salary) > 200000 ORDER BY p.department';

        $result = $this->_em->createQuery($dql)->getScalarResult();

        $this->assertEquals(2, count($result));
        $this->assertEquals(150000, $result[0][1]);
        $this->assertEquals(600000, $result[1][1]);
    }

    public function testOrderByResultVariableCollectionSize()
    {
        $dql = 'SELECT p.name, size(p.friends) AS friends ' .
               'FROM Doctrine\Tests\Models\Company\CompanyPerson p ' .
               'WHERE p.friends IS NOT EMPTY ' .
               'ORDER BY friends DESC, p.name DESC';

        $result = $this->_em->createQuery($dql)->getScalarResult();

        $this->assertEquals(4, count($result));

        $this->assertEquals("Jonathan W.", $result[0]['name']);
        $this->assertEquals(3, $result[0]['friends']);

        $this->assertEquals('Guilherme B.', $result[1]['name']);
        $this->assertEquals(2, $result[1]['friends']);

        $this->assertEquals('Benjamin E.', $result[2]['name']);
        $this->assertEquals(2, $result[2]['friends']);

        $this->assertEquals('Roman B.', $result[3]['name']);
        $this->assertEquals(1, $result[3]['friends']);
    }

    public function testIsNullAssocation()
    {
        $dql = 'SELECT p FROM Doctrine\Tests\Models\Company\CompanyPerson p '.
               'WHERE p.spouse IS NULL';
        $result = $this->_em->createQuery($dql)->getResult();

        $this->assertEquals(2, count($result));
        $this->assertTrue($result[0]->getId() > 0);
        $this->assertNull($result[0]->getSpouse());

        $this->assertTrue($result[1]->getId() > 0);
        $this->assertNull($result[1]->getSpouse());
    }

    public function testSelectSubselect()
    {
        $dql = 'SELECT p, (SELECT c.brand FROM Doctrine\Tests\Models\Company\CompanyCar c WHERE p.car = c) brandName '.
               'FROM Doctrine\Tests\Models\Company\CompanyManager p';
        $result = $this->_em->createQuery($dql)->getArrayResult();

        $this->assertEquals(1, count($result));
        $this->assertEquals("Caramba", $result[0]['brandName']);
    }

    public function testInSubselect()
    {
        $dql = "SELECT p.name FROM Doctrine\Tests\Models\Company\CompanyPerson p ".
               "WHERE p.name IN (SELECT n.name FROM Doctrine\Tests\Models\Company\CompanyPerson n WHERE n.name = 'Roman B.')";
        $result = $this->_em->createQuery($dql)->getScalarResult();

        $this->assertEquals(1, count($result));
        $this->assertEquals('Roman B.', $result[0]['name']);
    }

    public function testGroupByMultipleFields()
    {
        $dql = 'SELECT p.department, p.name, count(p.id) FROM Doctrine\Tests\Models\Company\CompanyEmployee p '.
               'GROUP BY p.department, p.name';
        $result = $this->_em->createQuery($dql)->getResult();

        $this->assertEquals(4, count($result));
    }

    public function testUpdateAs()
    {
        $dql = 'UPDATE Doctrine\Tests\Models\Company\CompanyEmployee AS p SET p.salary = 1';
        $this->_em->createQuery($dql)->execute();

        $this->assertTrue(count($this->_em->createQuery(
            'SELECT count(p.id) FROM Doctrine\Tests\Models\Company\CompanyEmployee p WHERE p.salary = 1')->getResult()) > 0);
    }

    public function testDeleteAs()
    {
        $dql = 'DELETE Doctrine\Tests\Models\Company\CompanyEmployee AS p';
        $this->_em->createQuery($dql)->getResult();

        $dql = 'SELECT count(p) FROM Doctrine\Tests\Models\Company\CompanyEmployee p';
        $result = $this->_em->createQuery($dql)->getSingleScalarResult();

        $this->assertEquals(0, $result);
    }

    public function generateFixture()
    {
        $car = new CompanyCar('Caramba');

        $manager1 = new CompanyManager();
        $manager1->setName('Roman B.');
        $manager1->setTitle('Foo');
        $manager1->setDepartment('IT');
        $manager1->setSalary(100000);
        $manager1->setCar($car);

        $person2 = new CompanyEmployee();
        $person2->setName('Benjamin E.');
        $person2->setDepartment('IT');
        $person2->setSalary(200000);

        $person3 = new CompanyEmployee();
        $person3->setName('Guilherme B.');
        $person3->setDepartment('IT2');
        $person3->setSalary(400000);

        $person4 = new CompanyEmployee();
        $person4->setName('Jonathan W.');
        $person4->setDepartment('IT2');
        $person4->setSalary(800000);

        $person2->setSpouse($person3);

        $manager1->addFriend($person4);
        $person2->addFriend($person3);
        $person2->addFriend($person4);
        $person3->addFriend($person4);

        $this->_em->persist($car);
        $this->_em->persist($manager1);
        $this->_em->persist($person2);
        $this->_em->persist($person3);
        $this->_em->persist($person4);
        $this->_em->flush();
        $this->_em->clear();
    }
}