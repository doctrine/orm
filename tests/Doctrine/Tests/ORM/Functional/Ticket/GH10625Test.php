<?php

declare(strict_types=1);

namespace Doctrine\Tests\ORM\Functional\Ticket;

use Doctrine\ORM\Mapping as ORM;
use Doctrine\Tests\OrmFunctionalTestCase;
use PHPUnit\Framework\Attributes\DataProvider;
use PHPUnit\Framework\Attributes\Group;

#[Group('GH-10625')]
class GH10625Test extends OrmFunctionalTestCase
{
    protected function setUp(): void
    {
        parent::setUp();

        $this->createSchemaForModels(
            GH10625Root::class,
            GH10625Middle::class,
            GH10625Leaf::class,
        );
    }

    #[DataProvider('queryClasses')]
    public function testLoadFieldsFromAllClassesInHierarchy(string $queryClass): void
    {
        $entity = new GH10625Leaf();

        $this->_em->persist($entity);
        $this->_em->flush();
        $this->_em->clear();

        $loadedEntity = $this->_em->find($queryClass, $entity->id);

        self::assertNotNull($loadedEntity);
        self::assertInstanceOf(GH10625Leaf::class, $loadedEntity);
    }

    public static function queryClasses(): array
    {
        return [
            'query via root entity' => [GH10625Root::class],
            'query via intermediate entity' => [GH10625Middle::class],
            'query via leaf entity' => [GH10625Leaf::class],
        ];
    }
}

#[ORM\Entity]
#[ORM\InheritanceType('SINGLE_TABLE')]
#[ORM\DiscriminatorMap([1 => 'GH10625Leaf'])] // <- This DiscriminatorMap contains the single non-abstract Entity class only
abstract class GH10625Root
{
    #[ORM\Id]
    #[ORM\GeneratedValue(strategy: 'AUTO')]
    #[ORM\Column(type: 'integer')]
    public int $id;
}

#[ORM\Entity]
abstract class GH10625Middle extends GH10625Root
{
}

#[ORM\Entity]
class GH10625Leaf extends GH10625Middle
{
}
