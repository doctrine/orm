<?php

namespace Doctrine\Tests\ORM\Functional\Ticket;

use Doctrine\Tests\Models\Issue5989\Issue5989Employee;
use Doctrine\Tests\Models\Issue5989\Issue5989Manager;
use Doctrine\Tests\Models\Issue5989\Issue5989Person;

/**
 * @group issue-5989
 */
class Issue5989Test extends \Doctrine\Tests\OrmFunctionalTestCase
{
    public function setUp()
    {
        $this->useModelSet('issue5989');
        parent::setUp();
    }

    public function testSimpleArrayTypeHydratedCorrectlyInJoinedInheritance()
    {
        $manager = new Issue5989Manager();

        $managerTags = ['tag1', 'tag2'];
        $manager->tags = $managerTags;
        $this->_em->persist($manager);

        $employee = new Issue5989Employee();

        $employeeTags =['tag2', 'tag3'];
        $employee->tags = $employeeTags;
        $this->_em->persist($employee);

        $this->_em->flush();

        $managerId = $manager->id;
        $employeeId = $employee->id;

        // clear entity manager so that $repository->find actually fetches them and uses the hydrator
        // instead of just returning the existing managed entities
        $this->_em->clear();

        $repository = $this->_em->getRepository(Issue5989Person::class);

        $manager = $repository->find($managerId);
        $employee = $repository->find($employeeId);

        static::assertEquals($managerTags, $manager->tags);
        static::assertEquals($employeeTags, $employee->tags);
    }
}
