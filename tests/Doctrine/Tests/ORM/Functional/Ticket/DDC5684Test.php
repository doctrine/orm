<?php

namespace Doctrine\Tests\ORM\Functional\Ticket;

use Doctrine\DBAL\Platforms\AbstractPlatform;
use Doctrine\DBAL\Types as DBALTypes;

class DDC5684Test extends \Doctrine\Tests\OrmFunctionalTestCase
{
    protected function setUp()
    {
        parent::setUp();

        if (DBALTypes\Type::hasType(DDC5684ObjectIdType::NAME)) {
            DBALTypes\Type::overrideType(DDC5684ObjectIdType::NAME, DDC5684ObjectIdType::CLASSNAME);
        } else {
            DBALTypes\Type::addType(DDC5684ObjectIdType::NAME, DDC5684ObjectIdType::CLASSNAME);
        }

        $this->_schemaTool->createSchema(array(
            $this->_em->getClassMetadata('Doctrine\Tests\ORM\Functional\Ticket\DDC5684Object')
        ));
    }

    public function testAutoIncrementIdWithCustomType()
    {
        $object = new DDC5684Object();
        $this->_em->persist($object);
        $this->_em->flush();

        $this->assertInstanceOf(DDC5684ObjectId::CLASSNAME, $object->id);
    }
}

class DDC5684ObjectIdType extends DBALTypes\IntegerType
{
    const NAME      = 'ticket_5684_object_id';
    const CLASSNAME = __CLASS__;

    public function convertToPHPValue($value, AbstractPlatform $platform)
    {
        $id = new DDC5684ObjectId();
        $id->value = $value;
        return $id;
    }

    public function convertToDatabaseValue($value, AbstractPlatform $platform)
    {
        return $value->value;
    }

    public function getName()
    {
        return self::NAME;
    }

    public function requiresSQLCommentHint(AbstractPlatform $platform)
    {
        return true;
    }
}

class DDC5684ObjectId
{
    const CLASSNAME = __CLASS__;

    public $value;
}

/**
 * @Entity
 * @Table(name="ticket_5684_objects")
 */
class DDC5684Object
{
    const CLASSNAME = __CLASS__;

    /**
    * @Id @Column(type="ticket_5684_object_id")
    * @GeneratedValue(strategy="AUTO")
    */
    public $id;
}
