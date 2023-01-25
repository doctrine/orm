<?php

declare(strict_types=1);

namespace Doctrine\Tests\ORM\Functional\Ticket;

use Doctrine\Tests\Models\GH9378\GH9378Employee;
use Doctrine\Tests\Models\GH9378\GH9378Person;
use Doctrine\Tests\OrmFunctionalTestCase;

/**
 * @requires PHP 7.4
 */
final class GH9378Test extends OrmFunctionalTestCase
{
    public function setUp(): void
    {
        parent::setUp();

        $this->createSchemaForModels(
            GH9378Employee::class,
            GH9378Person::class,
        );
    }

    public function testCanPersistVersionedEntityWithClassTableInheritance(): void
    {
        $firstName          = 'Max';
        $lastName           = 'MusterMann';
        $employeeId         = 'ABC123';
        
        $entity             = new GH9378Employee();
        $entity->firstName  = $firstName;
        $entity->lastName   = $lastName;
        $entity->employeeId = $employeeId;

        $this->_em->persist($entity);
        
        /*
         * Wthout the fix in Doctrine\ORM\Persisters\Entity\JoinedSubclassPersister
         * the flush would cause a
         * 
         * "LengthException: Unexpected empty result for database query."
         * 
         * as fetching generated columns and version column will take place
         * before the actual INSERT statement has been executed.
         * 
         * As we cannot test no exception, we simply test, that the entity's
         * properties have been persisted as expected.
         */
        $this->_em->flush();
        $this->_em->clear();

        $entity = $this->_em->find(GH9378Employee::class, 1);

        $this->assertSame($firstName, $entity->firstName);
        $this->assertSame($lastName, $entity->lastName);
        $this->assertSame($employeeId, $entity->employeeId);
    }
}
