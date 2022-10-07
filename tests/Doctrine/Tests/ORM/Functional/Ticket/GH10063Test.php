<?php

declare(strict_types=1);

namespace Doctrine\Tests\ORM\Functional\Ticket;

use Doctrine\ORM\Mapping\Column;
use Doctrine\ORM\Mapping\Entity;
use Doctrine\ORM\Mapping\GeneratedValue;
use Doctrine\ORM\Mapping\Id;
use Doctrine\Tests\OrmFunctionalTestCase;

final class GH10063Test extends OrmFunctionalTestCase
{
    protected function setUp(): void
    {
        parent::setUp();

        $this->createSchemaForModels(GH10063Entity::class);
    }

    public function testArrayOfEnums(): void
    {
        $entity = (new GH10063Entity())->setColors([GH10063Enum::Red, GH10063Enum::Green]);

        $this->_em->persist($entity);
        $this->_em->flush();
        $this->_em->clear();

        $entity = $this->_em->find(GH10063Entity::class, $entity->id);
        assert($entity instanceof GH10063Entity);
        self::assertEquals([GH10063Enum::Red, GH10063Enum::Green], $entity->getColors());
    }
}

/** @Entity */
class GH10063Entity
{
    /**
     * @Column
     * @Id
     * @GeneratedValue
     */
    public int $id;

    /**
     * @Column(type="simple_array", length=255, nullable=true, enumType=GH10063Enum::class)
     */
    private array $colors = [];

    /**
     * @param array<int, GH10063Enum> $colors
     */
    public function setColors(array $colors): self
    {
        $this->colors = $colors;
        return $this;
    }
    /**
     * @return array<int, GH10063Enum>
     */
    public function getColors(): array
    {
        return $this->colors;
    }
}

enum GH10063Enum: string
{
    case Red = 'red';
    case Green = 'green';
    case Blue = 'blue';
}
