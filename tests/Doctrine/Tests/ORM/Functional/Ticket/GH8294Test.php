<?php

use Doctrine\ORM\Mapping\MappingException;
use Doctrine\Tests\Models\GH8294\GH8294Entity;
use Doctrine\Tests\OrmFunctionalTestCase;

/**
 * @group GH-8294
 */
class GH8294Test extends OrmFunctionalTestCase
{
    /**
     * @group GH8294
     */
    public function testPersitingEntityWithInvalidTableName()
    {
        $entity = new GH8294Entity();

        $this->expectException(MappingException::class);
        $this->expectExceptionMessage("Invalid table name for class 'Doctrine\Tests\Models\GH8294\GH8294Entity' expected 'string' got 'array'.");
        $this->_em->persist($entity);
    }
}
