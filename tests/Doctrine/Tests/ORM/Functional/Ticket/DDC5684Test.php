<?php

namespace Doctrine\Tests\ORM\Functional\Ticket;

use Doctrine\DBAL\Platforms\AbstractPlatform;
use Doctrine\DBAL\Types as DBALTypes;

/**
 * This test verifies that custom post-insert identifiers respect type conversion semantics.
 * The generated identifier must be converted via DBAL types before populating the entity
 * identifier field.
 *
 * @group 5935 5684 6020 6152
 */
class DDC5684Test extends \Doctrine\Tests\OrmFunctionalTestCase
{
    protected function setUp()
    {
        parent::setUp();

        if (DBALTypes\Type::hasType(DDC5684ObjectIdType::class)) {
            DBALTypes\Type::overrideType(DDC5684ObjectIdType::class, DDC5684ObjectIdType::class);
        } else {
            DBALTypes\Type::addType(DDC5684ObjectIdType::class, DDC5684ObjectIdType::class);
        }

        $this->_schemaTool->createSchema([$this->_em->getClassMetadata(DDC5684Object::class)]);
    }

    protected function tearDown()
    {
        $this->_schemaTool->dropSchema([$this->_em->getClassMetadata(DDC5684Object::class)]);

        parent::tearDown();
    }

    public function testAutoIncrementIdWithCustomType()
    {
        $object = new DDC5684Object();
        $this->_em->persist($object);
        $this->_em->flush();

        $this->assertInstanceOf(DDC5684ObjectId::class, $object->id);
    }

    public function testFetchObjectWithAutoIncrementedCustomType()
    {
        $object = new DDC5684Object();
        $this->_em->persist($object);
        $this->_em->flush();
        $this->_em->clear();

        $rawId = $object->id->value;
        $object = $this->_em->find(DDC5684Object::class, new DDC5684ObjectId($rawId));

        $this->assertInstanceOf(DDC5684ObjectId::class, $object->id);
        $this->assertEquals($rawId, $object->id->value);
    }
}

class DDC5684ObjectIdType extends DBALTypes\IntegerType
{
    public function convertToPHPValue($value, AbstractPlatform $platform)
    {
        return new DDC5684ObjectId($value);
    }

    public function convertToDatabaseValue($value, AbstractPlatform $platform)
    {
        return $value->value;
    }

    public function getName()
    {
        return self::class;
    }

    public function requiresSQLCommentHint(AbstractPlatform $platform)
    {
        return true;
    }
}

class DDC5684ObjectId
{
    public $value;

    public function __construct($value)
    {
        $this->value = $value;
    }

    public function __toString()
    {
        return (string) $this->value;
    }
}

/**
 * @Entity
 * @Table(name="ticket_5684_objects")
 */
class DDC5684Object
{
    /**
     * @Id
     * @Column(type=Doctrine\Tests\ORM\Functional\Ticket\DDC5684ObjectIdType::class)
     * @GeneratedValue(strategy="AUTO")
     */
    public $id;
}
