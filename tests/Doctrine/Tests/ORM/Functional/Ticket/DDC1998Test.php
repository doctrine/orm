<?php

declare(strict_types=1);

namespace Doctrine\Tests\ORM\Functional\Ticket;

use Doctrine\DBAL\Platforms\AbstractPlatform;
use Doctrine\DBAL\Types\StringType;
use Doctrine\DBAL\Types\Type;
use Doctrine\ORM\Mapping\Column;
use Doctrine\ORM\Mapping\Entity;
use Doctrine\ORM\Mapping\Id;
use Doctrine\Tests\OrmFunctionalTestCase;

/** @group DDC-1998 */
class DDC1998Test extends OrmFunctionalTestCase
{
    public function testSqlConversionAsIdentifier(): void
    {
        Type::addType('ddc1998', DDC1998Type::class);

        $this->createSchemaForModels(DDC1998Entity::class);

        $entity     = new DDC1998Entity();
        $entity->id = new DDC1998Id('foo');

        $this->_em->persist($entity);
        $this->_em->flush();

        $entity->num++;

        $this->_em->flush();

        $this->_em->remove($entity);
        $this->_em->flush();
        $this->_em->clear();

        $found = $this->_em->find(DDC1998Entity::class, $entity->id);
        self::assertNull($found);

        $found = $this->_em->find(DDC1998Entity::class, 'foo');
        self::assertNull($found);

        self::assertCount(0, $this->_em->getRepository(DDC1998Entity::class)->findAll());
    }
}

/** @Entity */
class DDC1998Entity
{
    /**
     * @var string
     * @Id
     * @Column(type="ddc1998", length=255)
     */
    public $id;

    /**
     * @var int
     * @Column(type="integer")
     */
    public $num = 0;
}

class DDC1998Type extends StringType
{
    public const NAME = 'ddc1998';

    /**
     * {@inheritdoc}
     */
    public function convertToDatabaseValue($value, AbstractPlatform $platform)
    {
        return (string) $value;
    }

    /**
     * {@inheritDoc}
     */
    public function convertToPHPValue($value, AbstractPlatform $platform)
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
    /** @var string */
    private $val;

    public function __construct(string $val)
    {
        $this->val = $val;
    }

    public function __toString(): string
    {
        return $this->val;
    }
}
