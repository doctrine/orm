<?php

namespace Doctrine\Tests\ORM\Functional;

use Doctrine\DBAL\Types\Type as DBALType;
use Doctrine\Tests\DbalTypes\CustomIdObject;
use Doctrine\Tests\DbalTypes\PostInsertCustomIdObjectType;
use Doctrine\Tests\Models\PostInsertCustomType\PostInsertCustomTypeUser;
use Doctrine\Tests\OrmFunctionalTestCase;

class PostInsertCustomIdObjectTypeTest extends OrmFunctionalTestCase
{
    protected function setUp()
    {
        if (DBALType::hasType(PostInsertCustomIdObjectType::NAME)) {
            DBALType::overrideType(PostInsertCustomIdObjectType::NAME, PostInsertCustomIdObjectType::CLASSNAME);
        } else {
            DBALType::addType(PostInsertCustomIdObjectType::NAME, PostInsertCustomIdObjectType::CLASSNAME);
        }

        $this->useModelSet('post_insert_custom_id_object_type');

        parent::setUp();
    }

    public function testPersist()
    {
        $user = new PostInsertCustomTypeUser('foo');

        $this->_em->persist($user);
        $this->_em->flush();

        $this->assertInstanceOf(CustomIdObject::class, $user->id);
    }
}
