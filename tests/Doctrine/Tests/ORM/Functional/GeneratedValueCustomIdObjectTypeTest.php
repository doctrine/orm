<?php

namespace Doctrine\Tests\ORM\Functional;

use Doctrine\DBAL\Types\Type as DBALType;
use Doctrine\Tests\DbalTypes\CustomIdObject;
use Doctrine\Tests\DbalTypes\GeneratedValueCustomIdObjectType;
use Doctrine\Tests\Models\GeneratedValueCustomType\GeneratedValueCustomTypeUser;
use Doctrine\Tests\OrmFunctionalTestCase;

class GeneratedValueCustomIdObjectTypeTest extends OrmFunctionalTestCase
{
    protected function setUp()
    {
        if (DBALType::hasType(GeneratedValueCustomIdObjectType::NAME)) {
            DBALType::overrideType(GeneratedValueCustomIdObjectType::NAME, GeneratedValueCustomIdObjectType::CLASSNAME);
        } else {
            DBALType::addType(GeneratedValueCustomIdObjectType::NAME, GeneratedValueCustomIdObjectType::CLASSNAME);
        }

        $this->useModelSet('generated_value_custom_id_object_type');

        parent::setUp();
    }

    public function testPersist()
    {
        $user = new GeneratedValueCustomTypeUser('foo');

        $this->_em->persist($user);
        $this->_em->flush();

        $this->assertInstanceOf(CustomIdObject::class, $user->id);
    }
}
