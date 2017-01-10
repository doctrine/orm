<?php

namespace Doctrine\Tests\ORM\Functional;

use Doctrine\DBAL\Types\Type as DBALType;
use Doctrine\Tests\DbalTypes\NegativeToPositiveType;
use Doctrine\Tests\DbalTypes\UpperCaseStringType;
use Doctrine\Tests\Models\CustomType\CustomTypeChild;
use Doctrine\Tests\Models\CustomType\CustomTypeParent;
use Doctrine\Tests\Models\CustomType\CustomTypeUpperCase;
use Doctrine\Tests\OrmFunctionalTestCase;

class TypeValueSqlTest extends OrmFunctionalTestCase
{
    protected function setUp()
    {
        if (DBALType::hasType(UpperCaseStringType::NAME)) {
            DBALType::overrideType(UpperCaseStringType::NAME, UpperCaseStringType::class);
        } else {
            DBALType::addType(UpperCaseStringType::NAME, UpperCaseStringType::class);
        }

        if (DBALType::hasType(NegativeToPositiveType::NAME)) {
            DBALType::overrideType(NegativeToPositiveType::NAME, NegativeToPositiveType::class);
        } else {
            DBALType::addType(NegativeToPositiveType::NAME, NegativeToPositiveType::class);
        }

        $this->useModelSet('customtype');
        parent::setUp();
    }

    public function testUpperCaseStringType()
    {
        $entity = new CustomTypeUpperCase();
        $entity->lowerCaseString = 'foo';

        $this->em->persist($entity);
        $this->em->flush();

        $id = $entity->id;

        $this->em->clear();

        $entity = $this->em->find('\Doctrine\Tests\Models\CustomType\CustomTypeUpperCase', $id);

        self::assertEquals('foo', $entity->lowerCaseString, 'Entity holds lowercase string');
        self::assertEquals('FOO', $this->em->getConnection()->fetchColumn("select lowerCaseString from customtype_uppercases where id=".$entity->id.""), 'Database holds uppercase string');
    }

    /**
     * @group DDC-1642
     */
    public function testUpperCaseStringTypeWhenColumnNameIsDefined()
    {

        $entity = new CustomTypeUpperCase();
        $entity->lowerCaseString        = 'Some Value';
        $entity->namedLowerCaseString   = 'foo';

        $this->em->persist($entity);
        $this->em->flush();

        $id = $entity->id;

        $this->em->clear();

        $entity = $this->em->find('\Doctrine\Tests\Models\CustomType\CustomTypeUpperCase', $id);
        self::assertEquals('foo', $entity->namedLowerCaseString, 'Entity holds lowercase string');
        self::assertEquals('FOO', $this->em->getConnection()->fetchColumn("select named_lower_case_string from customtype_uppercases where id=".$entity->id.""), 'Database holds uppercase string');


        $entity->namedLowerCaseString   = 'bar';

        $this->em->persist($entity);
        $this->em->flush();

        $id = $entity->id;

        $this->em->clear();


        $entity = $this->em->find('\Doctrine\Tests\Models\CustomType\CustomTypeUpperCase', $id);
        self::assertEquals('bar', $entity->namedLowerCaseString, 'Entity holds lowercase string');
        self::assertEquals('BAR', $this->em->getConnection()->fetchColumn("select named_lower_case_string from customtype_uppercases where id=".$entity->id.""), 'Database holds uppercase string');
    }

    public function testTypeValueSqlWithAssociations()
    {
        $parent = new CustomTypeParent();
        $parent->customInteger = -1;
        $parent->child = new CustomTypeChild();

        $friend1 = new CustomTypeParent();
        $friend2 = new CustomTypeParent();

        $parent->addMyFriend($friend1);
        $parent->addMyFriend($friend2);

        $this->em->persist($parent);
        $this->em->persist($friend1);
        $this->em->persist($friend2);
        $this->em->flush();

        $parentId = $parent->id;

        $this->em->clear();

        $entity = $this->em->find(CustomTypeParent::class, $parentId);

        self::assertTrue($entity->customInteger < 0, 'Fetched customInteger negative');
        self::assertEquals(1, $this->em->getConnection()->fetchColumn("select customInteger from customtype_parents where id=".$entity->id.""), 'Database has stored customInteger positive');

        self::assertNotNull($parent->child, 'Child attached');
        self::assertCount(2, $entity->getMyFriends(), '2 friends attached');
    }

    public function testSelectDQL()
    {
        $parent = new CustomTypeParent();
        $parent->customInteger = -1;
        $parent->child = new CustomTypeChild();

        $this->em->persist($parent);
        $this->em->flush();

        $parentId = $parent->id;

        $this->em->clear();

        $query = $this->em->createQuery("SELECT p, p.customInteger, c from Doctrine\Tests\Models\CustomType\CustomTypeParent p JOIN p.child c where p.id = " . $parentId);

        $result = $query->getResult();

        self::assertEquals(1, count($result));
        self::assertInstanceOf(CustomTypeParent::class, $result[0][0]);
        self::assertEquals(-1, $result[0][0]->customInteger);

        self::assertEquals(-1, $result[0]['customInteger']);

        self::assertEquals('foo', $result[0][0]->child->lowerCaseString);
    }
}
