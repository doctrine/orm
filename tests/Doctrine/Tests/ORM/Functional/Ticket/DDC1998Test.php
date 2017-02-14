<?php

namespace Doctrine\Tests\ORM\Functional\Ticket;

use Doctrine\DBAL\Types\StringType;
use Doctrine\DBAL\Types\Type;
use Doctrine\DBAL\Platforms\AbstractPlatform;
use Doctrine\ORM\Annotation as ORM;

/**
 * @group DDC-1998
 */
class DDC1998Test extends \Doctrine\Tests\OrmFunctionalTestCase
{
    public function testSqlConversionAsIdentifier()
    {
        Type::addType('ddc1998', DDC1998Type::class);

        $this->schemaTool->createSchema(
            [
            $this->em->getClassMetadata(DDC1998Entity::class),
            ]
        );

        $entity = new DDC1998Entity();
        $entity->id = new DDC1998Id("foo");

        $this->em->persist($entity);
        $this->em->flush();

        $entity->num++;

        $this->em->flush();

        $this->em->remove($entity);
        $this->em->flush();
        $this->em->clear();


        $found = $this->em->find(DDC1998Entity::class, $entity->id);
        self::assertNull($found);

        $found = $this->em->find(DDC1998Entity::class, "foo");
        self::assertNull($found);

        self::assertEquals(0, count($this->em->getRepository(DDC1998Entity::class)->findAll()));
    }
}

/**
 * @ORM\Entity
 */
class DDC1998Entity
{
    /**
     * @ORM\Id @ORM\Column(type="ddc1998")
     */
    public $id;

    /**
     * @ORM\Column(type="integer")
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
