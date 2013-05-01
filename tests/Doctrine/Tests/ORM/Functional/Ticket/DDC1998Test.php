<?php

namespace Doctrine\Tests\ORM\Functional\Ticket;

use Doctrine\DBAL\Types\StringType;
use Doctrine\DBAL\Types\Type;
use Doctrine\DBAL\Platforms\AbstractPlatform;

/**
 * @group DDC-1998
 */
class DDC1998Test extends \Doctrine\Tests\OrmFunctionalTestCase
{
    public function testSqlConversionAsIdentifier()
    {
        Type::addType('ddc1998', __NAMESPACE__ . '\\DDC1998Type');

        $this->_schemaTool->createSchema(array(
            $this->_em->getClassMetadata(__NAMESPACE__ . '\\DDC1998Entity'),
        ));

        $entity = new DDC1998Entity();
        $entity->id = new DDC1998Id("foo");

        $this->_em->persist($entity);
        $this->_em->flush();

        $entity->num++;

        $this->_em->flush();

        $this->_em->remove($entity);
        $this->_em->flush();
        $this->_em->clear();


        $found = $this->_em->find(__NAMESPACE__ . '\\DDC1998Entity', $entity->id);
        $this->assertNull($found);

        $found = $this->_em->find(__NAMESPACE__ . '\\DDC1998Entity', "foo");
        $this->assertNull($found);

        $this->assertEquals(0, count($this->_em->getRepository(__NAMESPACE__ . '\\DDC1998Entity')->findAll()));
    }
}

/**
 * @Entity
 */
class DDC1998Entity
{
    /**
     * @Id @Column(type="ddc1998")
     */
    public $id;

    /**
     * @Column(type="integer")
     */
    public $num = 0;
}

class DDC1998Type extends StringType
{
    const NAME = 'ddc1998';

    /**
     * {@inheritdoc}
     */
    public function convertToDatabaseValue($value, AbstractPlatform $platform)
    {
        return (string)$value;
    }

    public function convertToPhpValue($value, AbstractPlatform $platform)
    {
        return new DDC1998Id($value);
    }

    /**
     * {@inheritdoc}
     */
    public function getName()
    {
        return self::NAME;
    }
}

class DDC1998Id
{
    private $val;

    public function __construct($val)
    {
        $this->val = $val;
    }

    public function __toString()
    {
        return $this->val;
    }
}
