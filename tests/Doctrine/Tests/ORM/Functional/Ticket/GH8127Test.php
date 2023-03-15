<?php

declare(strict_types=1);

namespace Doctrine\Tests\ORM\Functional\Ticket;

use Doctrine\ORM\Mapping as ORM;
use Doctrine\Tests\OrmFunctionalTestCase;

class GH8127Test extends OrmFunctionalTestCase
{
    protected function setUp(): void
    {
        parent::setUp();

        $this->createSchemaForModels(
            GH8127Root::class,
            GH8127Middle::class,
            GH8127Leaf::class
        );
    }

    /**
     * @dataProvider queryClasses
     */
    public function testLoadFieldsFromAllClassesInHierarchy(string $queryClass): void
    {
        $entity         = new GH8127Leaf();
        $entity->root   = 'root';
        $entity->middle = 'middle';
        $entity->leaf   = 'leaf';

        $this->_em->persist($entity);
        $this->_em->flush();
        $this->_em->clear();

        $loadedEntity = $this->_em->find($queryClass, $entity->id);

        self::assertSame('root', $loadedEntity->root);
        self::assertSame('middle', $loadedEntity->middle);
        self::assertSame('leaf', $loadedEntity->leaf);
    }

    public static function queryClasses(): array
    {
        return [
            'query via root entity' => [GH8127Root::class],
            'query via leaf entity' => [GH8127Leaf::class],
        ];
    }
}

/**
 * @ORM\Entity
 * @ORM\Table(name="root")
 * @ORM\InheritanceType("JOINED")
 * @ORM\DiscriminatorMap({ "leaf": "GH8127Leaf" })
 */
abstract class GH8127Root
{
    /**
     * @ORM\Id
     * @ORM\GeneratedValue(strategy="AUTO")
     * @ORM\Column(type="integer")
     *
     * @var int
     */
    public $id;

    /**
     * @ORM\Column
     *
     * @var string
     */
    public $root;
}

/**
 * @ORM\Entity
 */
abstract class GH8127Middle extends GH8127Root
{
    /**
     * @ORM\Column
     *
     * @var string
     */
    public $middle;
}

/**
 * @ORM\Entity
 */
class GH8127Leaf extends GH8127Middle
{
    /**
     * @ORM\Column
     *
     * @var string
     */
    public $leaf;
}
