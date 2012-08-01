<?php

namespace Doctrine\Tests\ORM\Functional;

use Doctrine\Tests\Models\Company\CompanyPerson,
    Doctrine\Tests\Models\Company\CompanyEmployee,
    Doctrine\Tests\Models\Company\CompanyManager,
    Doctrine\Tests\Models\Company\CompanyOrganization,
    Doctrine\Tests\Models\Company\CompanyEvent,
    Doctrine\Tests\Models\Company\CompanyAuction,
    Doctrine\Tests\Models\Company\CompanyRaffle,
    Doctrine\Tests\Models\Company\CompanyCar;

use Doctrine\Common\Collections\Criteria;

/**
 * Functional tests for the Class Table Inheritance mapping strategy.
 *
 * @author robo
 */
class ClassTableInheritanceTest extends \Doctrine\Tests\OrmFunctionalTestCase
{
    protected function setUp() {
        $this->useModelSet('company');
        parent::setUp();
        //$this->_em->getConnection()->getConfiguration()->setSQLLogger(new \Doctrine\DBAL\Logging\EchoSQLLogger);
    }

    public function testCRUD()
    {
        $person = new CompanyPerson;
        $person->setName('Roman S. Borschel');

        $this->_em->persist($person);

        $employee = new CompanyEmployee;
        $employee->setName('Roman S. Borschel');
        $employee->setSalary(100000);
        $employee->setDepartment('IT');

        $this->_em->persist($employee);

        $employee->setName('Guilherme Blanco');
        $this->_em->flush();

        $this->_em->clear();

        $query = $this->_em->createQuery("select p from Doctrine\Tests\Models\Company\CompanyPerson p order by p.name desc");

        $entities = $query->getResult();

        $this->assertEquals(2, count($entities));
        $this->assertInstanceOf('Doctrine\Tests\Models\Company\CompanyPerson', $entities[0]);
        $this->assertInstanceOf('Doctrine\Tests\Models\Company\CompanyEmployee', $entities[1]);
        $this->assertTrue(is_numeric($entities[0]->getId()));
        $this->assertTrue(is_numeric($entities[1]->getId()));
        $this->assertEquals('Roman S. Borschel', $entities[0]->getName());
        $this->assertEquals('Guilherme Blanco', $entities[1]->getName());
        $this->assertEquals(100000, $entities[1]->getSalary());

        $this->_em->clear();

        $query = $this->_em->createQuery("select p from Doctrine\Tests\Models\Company\CompanyEmployee p");

        $entities = $query->getResult();

        $this->assertEquals(1, count($entities));
        $this->assertInstanceOf('Doctrine\Tests\Models\Company\CompanyEmployee', $entities[0]);
        $this->assertTrue(is_numeric($entities[0]->getId()));
        $this->assertEquals('Guilherme Blanco', $entities[0]->getName());
        $this->assertEquals(100000, $entities[0]->getSalary());

        $this->_em->clear();

        $guilherme = $this->_em->getRepository(get_class($employee))->findOneBy(array('name' => 'Guilherme Blanco'));
        $this->assertInstanceOf('Doctrine\Tests\Models\Company\CompanyEmployee', $guilherme);
        $this->assertEquals('Guilherme Blanco', $guilherme->getName());

        $this->_em->clear();

        $query = $this->_em->createQuery("update Doctrine\Tests\Models\Company\CompanyEmployee p set p.name = ?1, p.department = ?2 where p.name='Guilherme Blanco' and p.salary = ?3");
        $query->setParameter(1, 'NewName', 'string');
        $query->setParameter(2, 'NewDepartment');
        $query->setParameter(3, 100000);
        $query->getSql();
        $numUpdated = $query->execute();
        $this->assertEquals(1, $numUpdated);

        $query = $this->_em->createQuery("delete from Doctrine\Tests\Models\Company\CompanyPerson p");
        $numDeleted = $query->execute();
        $this->assertEquals(2, $numDeleted);
    }

    public function testMultiLevelUpdateAndFind() {
    	$manager = new CompanyManager;
        $manager->setName('Roman S. Borschel');
        $manager->setSalary(100000);
        $manager->setDepartment('IT');
        $manager->setTitle('CTO');
        $this->_em->persist($manager);
        $this->_em->flush();

        $manager->setName('Roman B.');
        $manager->setSalary(119000);
        $manager->setTitle('CEO');
        $this->_em->persist($manager);
        $this->_em->flush();

        $this->_em->clear();

        $manager = $this->_em->find('Doctrine\Tests\Models\Company\CompanyManager', $manager->getId());

        $this->assertInstanceOf('Doctrine\Tests\Models\Company\CompanyManager', $manager);
        $this->assertEquals('Roman B.', $manager->getName());
        $this->assertEquals(119000, $manager->getSalary());
        $this->assertEquals('CEO', $manager->getTitle());
        $this->assertTrue(is_numeric($manager->getId()));
    }

    public function testFindOnBaseClass() {
        $manager = new CompanyManager;
        $manager->setName('Roman S. Borschel');
        $manager->setSalary(100000);
        $manager->setDepartment('IT');
        $manager->setTitle('CTO');
        $this->_em->persist($manager);
        $this->_em->flush();

        $this->_em->clear();

        $person = $this->_em->find('Doctrine\Tests\Models\Company\CompanyPerson', $manager->getId());

        $this->assertInstanceOf('Doctrine\Tests\Models\Company\CompanyManager', $person);
        $this->assertEquals('Roman S. Borschel', $person->getName());
        $this->assertEquals(100000, $person->getSalary());
        $this->assertEquals('CTO', $person->getTitle());
        $this->assertTrue(is_numeric($person->getId()));
        //$this->assertInstanceOf('Doctrine\Tests\Models\Company\CompanyCar', $person->getCar());
    }

    public function testSelfReferencingOneToOne() {
    	$manager = new CompanyManager;
        $manager->setName('John Smith');
        $manager->setSalary(100000);
        $manager->setDepartment('IT');
        $manager->setTitle('CTO');

        $wife = new CompanyPerson;
        $wife->setName('Mary Smith');
        $wife->setSpouse($manager);

        $this->assertSame($manager, $wife->getSpouse());
        $this->assertSame($wife, $manager->getSpouse());

        $this->_em->persist($manager);
        $this->_em->persist($wife);

        $this->_em->flush();

        //var_dump($this->_em->getConnection()->fetchAll('select * from company_persons'));
        //var_dump($this->_em->getConnection()->fetchAll('select * from company_employees'));
        //var_dump($this->_em->getConnection()->fetchAll('select * from company_managers'));

        $this->_em->clear();

        $query = $this->_em->createQuery('select p, s from Doctrine\Tests\Models\Company\CompanyPerson p join p.spouse s where p.name=\'Mary Smith\'');

        $result = $query->getResult();
        $this->assertEquals(1, count($result));
        $this->assertInstanceOf('Doctrine\Tests\Models\Company\CompanyPerson', $result[0]);
        $this->assertEquals('Mary Smith', $result[0]->getName());
        $this->assertInstanceOf('Doctrine\Tests\Models\Company\CompanyEmployee', $result[0]->getSpouse());
        $this->assertEquals('John Smith', $result[0]->getSpouse()->getName());
        $this->assertSame($result[0], $result[0]->getSpouse()->getSpouse());
    }

    public function testSelfReferencingManyToMany()
    {
        $person1 = new CompanyPerson;
        $person1->setName('Roman');

        $person2 = new CompanyPerson;
        $person2->setName('Jonathan');

        $person1->addFriend($person2);

        $this->assertEquals(1, count($person1->getFriends()));
        $this->assertEquals(1, count($person2->getFriends()));


        $this->_em->persist($person1);
        $this->_em->persist($person2);

        $this->_em->flush();

        $this->_em->clear();

        $query = $this->_em->createQuery('select p, f from Doctrine\Tests\Models\Company\CompanyPerson p join p.friends f where p.name=?1');
        $query->setParameter(1, 'Roman');

        $result = $query->getResult();
        $this->assertEquals(1, count($result));
        $this->assertEquals(1, count($result[0]->getFriends()));
        $this->assertEquals('Roman', $result[0]->getName());

        $friends = $result[0]->getFriends();
        $this->assertEquals('Jonathan', $friends[0]->getName());
    }

    public function testLazyLoading1()
    {
        $org = new CompanyOrganization;
        $event1 = new CompanyAuction;
        $event1->setData('auction');
        $org->addEvent($event1);
        $event2 = new CompanyRaffle;
        $event2->setData('raffle');
        $org->addEvent($event2);

        $this->_em->persist($org);
        $this->_em->flush();
        $this->_em->clear();

        $orgId = $org->getId();

        $q = $this->_em->createQuery('select a from Doctrine\Tests\Models\Company\CompanyOrganization a where a.id = ?1');
        $q->setParameter(1, $orgId);

        $result = $q->getResult();

        $this->assertEquals(1, count($result));
        $this->assertInstanceOf('Doctrine\Tests\Models\Company\CompanyOrganization', $result[0]);
        $this->assertNull($result[0]->getMainEvent());

        $events = $result[0]->getEvents();

        $this->assertInstanceOf('Doctrine\ORM\PersistentCollection', $events);
        $this->assertFalse($events->isInitialized());

        $this->assertEquals(2, count($events));
        if ($events[0] instanceof CompanyAuction) {
            $this->assertInstanceOf('Doctrine\Tests\Models\Company\CompanyRaffle', $events[1]);
        } else {
            $this->assertInstanceOf('Doctrine\Tests\Models\Company\CompanyRaffle', $events[0]);
            $this->assertInstanceOf('Doctrine\Tests\Models\Company\CompanyAuction', $events[1]);
        }
    }


    public function testLazyLoading2()
    {
        $org = new CompanyOrganization;
        $event1 = new CompanyAuction;
        $event1->setData('auction');
        $org->setMainEvent($event1);

        $this->_em->persist($org);
        $this->_em->flush();
        $this->_em->clear();

        $q = $this->_em->createQuery('select a from Doctrine\Tests\Models\Company\CompanyEvent a where a.id = ?1');
        $q->setParameter(1, $event1->getId());

        $result = $q->getResult();
        $this->assertEquals(1, count($result));
        $this->assertInstanceOf('Doctrine\Tests\Models\Company\CompanyAuction', $result[0], sprintf("Is of class %s",get_class($result[0])));

        $this->_em->clear();

        $q = $this->_em->createQuery('select a from Doctrine\Tests\Models\Company\CompanyOrganization a where a.id = ?1');
        $q->setParameter(1, $org->getId());

        $result = $q->getResult();

        $this->assertEquals(1, count($result));
        $this->assertInstanceOf('Doctrine\Tests\Models\Company\CompanyOrganization', $result[0]);

        $mainEvent = $result[0]->getMainEvent();
        // mainEvent should have been loaded because it can't be lazy
        $this->assertInstanceOf('Doctrine\Tests\Models\Company\CompanyAuction', $mainEvent);
        $this->assertNotInstanceOf('Doctrine\ORM\Proxy\Proxy', $mainEvent);
    }

    /**
     * @group DDC-368
     */
    public function testBulkUpdateIssueDDC368()
    {
        $dql = 'UPDATE Doctrine\Tests\Models\Company\CompanyEmployee AS p SET p.salary = 1';
        $this->_em->createQuery($dql)->execute();

        $this->assertTrue(count($this->_em->createQuery(
            'SELECT count(p.id) FROM Doctrine\Tests\Models\Company\CompanyEmployee p WHERE p.salary = 1')
            ->getResult()) > 0);
    }

    /**
     * @group DDC-1341
     */
    public function testBulkUpdateNonScalarParameterDDC1341()
    {
        $dql   = 'UPDATE Doctrine\Tests\Models\Company\CompanyEmployee AS p SET p.startDate = ?0 WHERE p.department = ?1';
        $query = $this->_em->createQuery($dql)
            ->setParameter(0, new \DateTime())
            ->setParameter(1, 'IT');

        $result = $query->execute();

    }

    /**
     * @group DDC-130
     */
    public function testDeleteJoinTableRecords()
    {
        #$this->markTestSkipped('Nightmare! friends adds both ID 6-7 and 7-6 into two rows of the join table. How to detect this?');

        $employee1 = new CompanyEmployee();
        $employee1->setName('gblanco');
        $employee1->setSalary(0);
        $employee1->setDepartment('IT');

        $employee2 = new CompanyEmployee();
        $employee2->setName('jwage');
        $employee2->setSalary(0);
        $employee2->setDepartment('IT');

        $employee1->addFriend($employee2);

        $this->_em->persist($employee1);
        $this->_em->persist($employee2);
        $this->_em->flush();

        $employee1Id = $employee1->getId();

        $this->_em->remove($employee1);
        $this->_em->flush();

        $this->assertNull($this->_em->find(get_class($employee1), $employee1Id));
    }

    /**
     * @group DDC-728
     */
    public function testQueryForInheritedSingleValuedAssociation()
    {
        $manager = new CompanyManager();
        $manager->setName('gblanco');
        $manager->setSalary(1234);
        $manager->setTitle('Awesome!');
        $manager->setDepartment('IT');

        $person = new CompanyPerson();
        $person->setName('spouse');

        $manager->setSpouse($person);

        $this->_em->persist($manager);
        $this->_em->persist($person);
        $this->_em->flush();
        $this->_em->clear();

        $dql = "SELECT m FROM Doctrine\Tests\Models\Company\CompanyManager m WHERE m.spouse = ?1";
        $dqlManager = $this->_em->createQuery($dql)->setParameter(1, $person->getId())->getSingleResult();

        $this->assertEquals($manager->getId(), $dqlManager->getId());
        $this->assertEquals($person->getId(), $dqlManager->getSpouse()->getId());
    }

    /**
     * @group DDC-817
     */
    public function testFindByAssociation()
    {
        $manager = new CompanyManager();
        $manager->setName('gblanco');
        $manager->setSalary(1234);
        $manager->setTitle('Awesome!');
        $manager->setDepartment('IT');

        $person = new CompanyPerson();
        $person->setName('spouse');

        $manager->setSpouse($person);

        $this->_em->persist($manager);
        $this->_em->persist($person);
        $this->_em->flush();
        $this->_em->clear();

        $repos = $this->_em->getRepository('Doctrine\Tests\Models\Company\CompanyManager');
        $pmanager = $repos->findOneBy(array('spouse' => $person->getId()));

        $this->assertEquals($manager->getId(), $pmanager->getId());

        $repos = $this->_em->getRepository('Doctrine\Tests\Models\Company\CompanyPerson');
        $pmanager = $repos->findOneBy(array('spouse' => $person->getId()));

        $this->assertEquals($manager->getId(), $pmanager->getId());
    }

    /**
     * @group DDC-834
     */
    public function testGetReferenceEntityWithSubclasses()
    {
        $manager = new CompanyManager();
        $manager->setName('gblanco');
        $manager->setSalary(1234);
        $manager->setTitle('Awesome!');
        $manager->setDepartment('IT');

        $this->_em->persist($manager);
        $this->_em->flush();
        $this->_em->clear();

        $ref = $this->_em->getReference('Doctrine\Tests\Models\Company\CompanyPerson', $manager->getId());
        $this->assertNotInstanceOf('Doctrine\ORM\Proxy\Proxy', $ref, "Cannot Request a proxy from a class that has subclasses.");
        $this->assertInstanceOf('Doctrine\Tests\Models\Company\CompanyPerson', $ref);
        $this->assertInstanceOf('Doctrine\Tests\Models\Company\CompanyEmployee', $ref, "Direct fetch of the reference has to load the child class Emplyoee directly.");
        $this->_em->clear();

        $ref = $this->_em->getReference('Doctrine\Tests\Models\Company\CompanyManager', $manager->getId());
        $this->assertInstanceOf('Doctrine\ORM\Proxy\Proxy', $ref, "A proxy can be generated only if no subclasses exists for the requested reference.");
    }

    /**
     * @group DDC-992
     */
    public function testGetSubClassManyToManyCollection()
    {
        $manager = new CompanyManager();
        $manager->setName('gblanco');
        $manager->setSalary(1234);
        $manager->setTitle('Awesome!');
        $manager->setDepartment('IT');

        $person = new CompanyPerson();
        $person->setName('friend');

        $manager->addFriend($person);

        $this->_em->persist($manager);
        $this->_em->persist($person);
        $this->_em->flush();
        $this->_em->clear();

        $manager = $this->_em->find('Doctrine\Tests\Models\Company\CompanyManager', $manager->getId());
        $this->assertEquals(1, count($manager->getFriends()));
    }

    /**
     * @group DDC-1777
     */
    public function testExistsSubclass()
    {
        $manager = new CompanyManager();
        $manager->setName('gblanco');
        $manager->setSalary(1234);
        $manager->setTitle('Awesome!');
        $manager->setDepartment('IT');

        $this->assertFalse($this->_em->getUnitOfWork()->getEntityPersister(get_class($manager))->exists($manager));

        $this->_em->persist($manager);
        $this->_em->flush();

        $this->assertTrue($this->_em->getUnitOfWork()->getEntityPersister(get_class($manager))->exists($manager));
    }

    /**
     * @group DDC-1637
     */
    public function testMatching()
    {
        $manager = new CompanyManager();
        $manager->setName('gblanco');
        $manager->setSalary(1234);
        $manager->setTitle('Awesome!');
        $manager->setDepartment('IT');

        $this->_em->persist($manager);
        $this->_em->flush();

        $repository = $this->_em->getRepository("Doctrine\Tests\Models\Company\CompanyEmployee");
        $users = $repository->matching(new Criteria(
            Criteria::expr()->eq('department', 'IT')
        ));
        $this->assertEquals(1, count($users));

        $repository = $this->_em->getRepository("Doctrine\Tests\Models\Company\CompanyManager");
        $users = $repository->matching(new Criteria(
            Criteria::expr()->eq('department', 'IT')
        ));
        $this->assertEquals(1, count($users));
    }
}
