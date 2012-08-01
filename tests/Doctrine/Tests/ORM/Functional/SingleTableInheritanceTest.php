<?php

namespace Doctrine\Tests\ORM\Functional;

use Doctrine\ORM\Mapping\ClassMetadata;
use Doctrine\Common\Collections\Criteria;

class SingleTableInheritanceTest extends \Doctrine\Tests\OrmFunctionalTestCase
{
    private $salesPerson;
    private $engineers = array();
    private $fix;
    private $flex;
    private $ultra;

    public function setUp()
    {
        $this->useModelSet('company');
        parent::setUp();
    }

    public function persistRelatedEmployees()
    {
        $this->salesPerson = new \Doctrine\Tests\Models\Company\CompanyEmployee();
        $this->salesPerson->setName('Poor Sales Guy');
        $this->salesPerson->setDepartment('Sales');
        $this->salesPerson->setSalary(100);

        $engineer1 = new \Doctrine\Tests\Models\Company\CompanyEmployee();
        $engineer1->setName('Roman B.');
        $engineer1->setDepartment('IT');
        $engineer1->setSalary(100);
        $this->engineers[] = $engineer1;

        $engineer2 = new \Doctrine\Tests\Models\Company\CompanyEmployee();
        $engineer2->setName('Jonathan W.');
        $engineer2->setDepartment('IT');
        $engineer2->setSalary(100);
        $this->engineers[] = $engineer2;

        $engineer3 = new \Doctrine\Tests\Models\Company\CompanyEmployee();
        $engineer3->setName('Benjamin E.');
        $engineer3->setDepartment('IT');
        $engineer3->setSalary(100);
        $this->engineers[] = $engineer3;

        $engineer4 = new \Doctrine\Tests\Models\Company\CompanyEmployee();
        $engineer4->setName('Guilherme B.');
        $engineer4->setDepartment('IT');
        $engineer4->setSalary(100);
        $this->engineers[] = $engineer4;

        $this->_em->persist($this->salesPerson);
        $this->_em->persist($engineer1);
        $this->_em->persist($engineer2);
        $this->_em->persist($engineer3);
        $this->_em->persist($engineer4);
    }

    public function loadFullFixture()
    {
        $this->persistRelatedEmployees();

        $this->fix = new \Doctrine\Tests\Models\Company\CompanyFixContract();
        $this->fix->setFixPrice(1000);
        $this->fix->setSalesPerson($this->salesPerson);
        $this->fix->addEngineer($this->engineers[0]);
        $this->fix->addEngineer($this->engineers[1]);
        $this->fix->markCompleted();

        $this->flex = new \Doctrine\Tests\Models\Company\CompanyFlexContract();
        $this->flex->setSalesPerson($this->salesPerson);
        $this->flex->setHoursWorked(100);
        $this->flex->setPricePerHour(100);
        $this->flex->addEngineer($this->engineers[2]);
        $this->flex->addEngineer($this->engineers[1]);
        $this->flex->addEngineer($this->engineers[3]);
        $this->flex->markCompleted();

        $this->ultra = new \Doctrine\Tests\Models\Company\CompanyFlexUltraContract();
        $this->ultra->setSalesPerson($this->salesPerson);
        $this->ultra->setHoursWorked(150);
        $this->ultra->setPricePerHour(150);
        $this->ultra->setMaxPrice(7000);
        $this->ultra->addEngineer($this->engineers[3]);
        $this->ultra->addEngineer($this->engineers[0]);

        $this->_em->persist($this->fix);
        $this->_em->persist($this->flex);
        $this->_em->persist($this->ultra);
        $this->_em->flush();
        $this->_em->clear();
    }

    public function testPersistChildOfBaseClass()
    {
        $this->persistRelatedEmployees();

        $fixContract = new \Doctrine\Tests\Models\Company\CompanyFixContract();
        $fixContract->setFixPrice(1000);
        $fixContract->setSalesPerson($this->salesPerson);

        $this->_em->persist($fixContract);
        $this->_em->flush();
        $this->_em->clear();

        $contract = $this->_em->find('Doctrine\Tests\Models\Company\CompanyFixContract', $fixContract->getId());

        $this->assertInstanceOf('Doctrine\Tests\Models\Company\CompanyFixContract', $contract);
        $this->assertEquals(1000, $contract->getFixPrice());
        $this->assertEquals($this->salesPerson->getId(), $contract->getSalesPerson()->getId());
    }

    public function testPersistDeepChildOfBaseClass()
    {
        $this->persistRelatedEmployees();

        $ultraContract = new \Doctrine\Tests\Models\Company\CompanyFlexUltraContract();
        $ultraContract->setSalesPerson($this->salesPerson);
        $ultraContract->setHoursWorked(100);
        $ultraContract->setPricePerHour(50);
        $ultraContract->setMaxPrice(7000);

        $this->_em->persist($ultraContract);
        $this->_em->flush();
        $this->_em->clear();

        $contract = $this->_em->find('Doctrine\Tests\Models\Company\CompanyFlexUltraContract', $ultraContract->getId());

        $this->assertInstanceOf('Doctrine\Tests\Models\Company\CompanyFlexUltraContract', $contract);
        $this->assertEquals(7000, $contract->getMaxPrice());
        $this->assertEquals(100, $contract->getHoursWorked());
        $this->assertEquals(50, $contract->getPricePerHour());
    }

    public function testChildClassLifecycleUpdate()
    {
        $this->loadFullFixture();

        $fix = $this->_em->find('Doctrine\Tests\Models\Company\CompanyContract', $this->fix->getId());
        $fix->setFixPrice(2500);

        $this->_em->flush();
        $this->_em->clear();

        $newFix = $this->_em->find('Doctrine\Tests\Models\Company\CompanyContract', $this->fix->getId());
        $this->assertEquals(2500, $newFix->getFixPrice());
    }

    public function testChildClassLifecycleRemove()
    {
        $this->loadFullFixture();

        $fix = $this->_em->find('Doctrine\Tests\Models\Company\CompanyContract', $this->fix->getId());
        $this->_em->remove($fix);
        $this->_em->flush();

        $this->assertNull($this->_em->find('Doctrine\Tests\Models\Company\CompanyContract', $this->fix->getId()));
    }

    public function testFindAllForAbstractBaseClass()
    {
        $this->loadFullFixture();
        $contracts = $this->_em->getRepository('Doctrine\Tests\Models\Company\CompanyContract')->findAll();

        $this->assertEquals(3, count($contracts));
        $this->assertContainsOnly('Doctrine\Tests\Models\Company\CompanyContract', $contracts);
    }

    public function testFindAllForChildClass()
    {
        $this->loadFullFixture();

        $this->assertEquals(1, count($this->_em->getRepository('Doctrine\Tests\Models\Company\CompanyFixContract')->findAll()));
        $this->assertEquals(2, count($this->_em->getRepository('Doctrine\Tests\Models\Company\CompanyFlexContract')->findAll()));
        $this->assertEquals(1, count($this->_em->getRepository('Doctrine\Tests\Models\Company\CompanyFlexUltraContract')->findAll()));
    }

    public function testFindForAbstractBaseClass()
    {
        $this->loadFullFixture();

        $contract = $this->_em->find('Doctrine\Tests\Models\Company\CompanyContract', $this->fix->getId());

        $this->assertInstanceOf('Doctrine\Tests\Models\Company\CompanyFixContract', $contract);
        $this->assertEquals(1000, $contract->getFixPrice());
    }

    public function testQueryForAbstractBaseClass()
    {
        $this->loadFullFixture();

        $contracts = $this->_em->createQuery('SELECT c FROM Doctrine\Tests\Models\Company\CompanyContract c')->getResult();

        $this->assertEquals(3, count($contracts));
        $this->assertContainsOnly('Doctrine\Tests\Models\Company\CompanyContract', $contracts);
    }

    public function testQueryForChildClass()
    {
        $this->loadFullFixture();

        $this->assertEquals(1, count($this->_em->createQuery('SELECT c FROM Doctrine\Tests\Models\Company\CompanyFixContract c')->getResult()));
        $this->assertEquals(2, count($this->_em->createQuery('SELECT c FROM Doctrine\Tests\Models\Company\CompanyFlexContract c')->getResult()));
        $this->assertEquals(1, count($this->_em->createQuery('SELECT c FROM Doctrine\Tests\Models\Company\CompanyFlexUltraContract c')->getResult()));
    }

    public function testQueryBaseClassWithJoin()
    {
        $this->loadFullFixture();

        $contracts = $this->_em->createQuery('SELECT c, p FROM Doctrine\Tests\Models\Company\CompanyContract c JOIN c.salesPerson p')->getResult();
        $this->assertEquals(3, count($contracts));
        $this->assertContainsOnly('Doctrine\Tests\Models\Company\CompanyContract', $contracts);
    }

    public function testQueryScalarWithDiscrimnatorValue()
    {
        $this->loadFullFixture();

        $contracts = $this->_em->createQuery('SELECT c FROM Doctrine\Tests\Models\Company\CompanyContract c ORDER BY c.id')->getScalarResult();

        $discrValues = \array_map(function($a) {
            return $a['c_discr'];
        }, $contracts);

        sort($discrValues);

        $this->assertEquals(array('fix', 'flexible', 'flexultra'), $discrValues);
    }

    public function testQueryChildClassWithCondition()
    {
        $this->loadFullFixture();

        $dql = 'SELECT c FROM Doctrine\Tests\Models\Company\CompanyFixContract c WHERE c.fixPrice = ?1';
        $contract = $this->_em->createQuery($dql)->setParameter(1, 1000)->getSingleResult();

        $this->assertInstanceOf('Doctrine\Tests\Models\Company\CompanyFixContract', $contract);
        $this->assertEquals(1000, $contract->getFixPrice());
    }

    public function testUpdateChildClassWithCondition()
    {
        $this->loadFullFixture();

        $dql = 'UPDATE Doctrine\Tests\Models\Company\CompanyFlexContract c SET c.hoursWorked = c.hoursWorked * 2 WHERE c.hoursWorked = 150';
        $affected = $this->_em->createQuery($dql)->execute();

        $this->assertEquals(1, $affected);

        $flexContract = $this->_em->find('Doctrine\Tests\Models\Company\CompanyContract', $this->flex->getId());
        $ultraContract = $this->_em->find('Doctrine\Tests\Models\Company\CompanyContract', $this->ultra->getId());

        $this->assertEquals(300, $ultraContract->getHoursWorked());
        $this->assertEquals(100, $flexContract->getHoursWorked());
    }

    public function testUpdateBaseClassWithCondition()
    {
        $this->loadFullFixture();

        $dql = 'UPDATE Doctrine\Tests\Models\Company\CompanyContract c SET c.completed = true WHERE c.completed = false';
        $affected = $this->_em->createQuery($dql)->execute();

        $this->assertEquals(1, $affected);

        $dql = 'UPDATE Doctrine\Tests\Models\Company\CompanyContract c SET c.completed = false';
        $affected = $this->_em->createQuery($dql)->execute();

        $this->assertEquals(3, $affected);
    }

    public function testDeleteByChildClassCondition()
    {
        $this->loadFullFixture();

        $dql = 'DELETE Doctrine\Tests\Models\Company\CompanyFlexContract c';
        $affected = $this->_em->createQuery($dql)->execute();

        $this->assertEquals(2, $affected);
    }

    public function testDeleteByBaseClassCondition()
    {
        $this->loadFullFixture();

        $dql = "DELETE Doctrine\Tests\Models\Company\CompanyContract c WHERE c.completed = true";
        $affected = $this->_em->createQuery($dql)->execute();

        $this->assertEquals(2, $affected);

        $contracts = $this->_em->createQuery('SELECT c FROM Doctrine\Tests\Models\Company\CompanyContract c')->getResult();
        $this->assertEquals(1, count($contracts));

        $this->assertFalse($contracts[0]->isCompleted(), "Only non completed contracts should be left.");
    }

    /**
     * @group DDC-130
     */
    public function testDeleteJoinTableRecords()
    {
        $this->loadFullFixture();

        // remove managed copy of the fix contract
        $this->_em->remove($this->_em->find(get_class($this->fix), $this->fix->getId()));
        $this->_em->flush();

        $this->assertNull($this->_em->find(get_class($this->fix), $this->fix->getId()), "Contract should not be present in the database anymore.");
    }

    /**
     * @group DDC-817
     */
    public function testFindByAssociation()
    {
        $this->loadFullFixture();

        $repos = $this->_em->getRepository("Doctrine\Tests\Models\Company\CompanyContract");
        $contracts = $repos->findBy(array('salesPerson' => $this->salesPerson->getId()));
        $this->assertEquals(3, count($contracts), "There should be 3 entities related to " . $this->salesPerson->getId() . " for 'Doctrine\Tests\Models\Company\CompanyContract'");

        $repos = $this->_em->getRepository("Doctrine\Tests\Models\Company\CompanyFixContract");
        $contracts = $repos->findBy(array('salesPerson' => $this->salesPerson->getId()));
        $this->assertEquals(1, count($contracts), "There should be 1 entities related to " . $this->salesPerson->getId() . " for 'Doctrine\Tests\Models\Company\CompanyFixContract'");

        $repos = $this->_em->getRepository("Doctrine\Tests\Models\Company\CompanyFlexContract");
        $contracts = $repos->findBy(array('salesPerson' => $this->salesPerson->getId()));
        $this->assertEquals(2, count($contracts), "There should be 2 entities related to " . $this->salesPerson->getId() . " for 'Doctrine\Tests\Models\Company\CompanyFlexContract'");

        $repos = $this->_em->getRepository("Doctrine\Tests\Models\Company\CompanyFlexUltraContract");
        $contracts = $repos->findBy(array('salesPerson' => $this->salesPerson->getId()));
        $this->assertEquals(1, count($contracts), "There should be 1 entities related to " . $this->salesPerson->getId() . " for 'Doctrine\Tests\Models\Company\CompanyFlexUltraContract'");
    }

    /**
     * @group DDC-1637
     */
    public function testInheritanceMatching()
    {
        $this->loadFullFixture();

        $repository = $this->_em->getRepository("Doctrine\Tests\Models\Company\CompanyContract");
        $contracts = $repository->matching(new Criteria(
            Criteria::expr()->eq('salesPerson', $this->salesPerson->getId())
        ));
        $this->assertEquals(3, count($contracts));

        $repository = $this->_em->getRepository("Doctrine\Tests\Models\Company\CompanyFixContract");
        $contracts = $repository->matching(new Criteria(
            Criteria::expr()->eq('salesPerson', $this->salesPerson->getId())
        ));
        $this->assertEquals(1, count($contracts));
    }

    /**
     * @group DDC-834
     */
    public function testGetReferenceEntityWithSubclasses()
    {
        $this->loadFullFixture();

        $ref = $this->_em->getReference('Doctrine\Tests\Models\Company\CompanyContract', $this->fix->getId());
        $this->assertNotInstanceOf('Doctrine\ORM\Proxy\Proxy', $ref, "Cannot Request a proxy from a class that has subclasses.");
        $this->assertInstanceOf('Doctrine\Tests\Models\Company\CompanyContract', $ref);
        $this->assertInstanceOf('Doctrine\Tests\Models\Company\CompanyFixContract', $ref, "Direct fetch of the reference has to load the child class Emplyoee directly.");
        $this->_em->clear();

        $ref = $this->_em->getReference('Doctrine\Tests\Models\Company\CompanyFixContract', $this->fix->getId());
        $this->assertInstanceOf('Doctrine\ORM\Proxy\Proxy', $ref, "A proxy can be generated only if no subclasses exists for the requested reference.");
    }

    /**
     * @group DDC-952
     */
    public function testEagerLoadInheritanceHierachy()
    {
        $this->loadFullFixture();

        $dql = 'SELECT f FROM Doctrine\Tests\Models\Company\CompanyFixContract f WHERE f.id = ?1';
        $contract = $this->_em->createQuery($dql)
                              ->setFetchMode('Doctrine\Tests\Models\Company\CompanyFixContract', 'salesPerson', ClassMetadata::FETCH_EAGER)
                              ->setParameter(1, $this->fix->getId())
                              ->getSingleResult();

        $this->assertNotInstanceOf('Doctrine\ORM\Proxy\Proxy', $contract->getSalesPerson());
    }
}
