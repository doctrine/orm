<?php

namespace Doctrine\Tests\ORM\Functional;

use Doctrine\DBAL\Types\Type as DBALType;
use Doctrine\Tests\DbalTypes\CustomIdObject;
use Doctrine\Tests\DbalTypes\CustomIdObjectType;
use Doctrine\Tests\Models\ClassTableInheritanceCustomType\CustomIdObjectTypeChild;
use Doctrine\Tests\Models\ClassTableInheritanceCustomType\CustomIdObjectTypeParent;
use Doctrine\Tests\OrmFunctionalTestCase;

/**
 * Functional tests for the Class Table Inheritance mapping strategy with custom id object types.
 */
class ClassTableInheritanceCustomIdObjectTypeTest extends OrmFunctionalTestCase
{
    protected function setUp()
    {
        if (DBALType::hasType(CustomIdObjectType::NAME)) {
            DBALType::overrideType(CustomIdObjectType::NAME, CustomIdObjectType::class);
        } else {
            DBALType::addType(CustomIdObjectType::NAME, CustomIdObjectType::class);
        }

        $this->useModelSet('class_table_inheritance_custom_id_object_type');

        parent::setUp();
    }

    public function testDelete()
    {
        $object = new CustomIdObjectTypeChild(new CustomIdObject('foo'));
        $object->name = 'Test';

        // persist parent
        $this->_em->persist($object);
        $this->_em->flush();

        // save id for later use
        $id = $object->id;

        // get child
        $object2 = $this->_em->find(CustomIdObjectTypeChild::class, $id);

        // remove child
        $this->_em->remove($object2);
        $this->_em->flush();

        $this->assertNull($this->_em->find(CustomIdObjectTypeChild::class, $id));
    }
}
