<?php

declare(strict_types=1);

namespace Doctrine\Tests\ORM\Id;

use Doctrine\ORM\EntityManagerInterface;
use Doctrine\ORM\Exception\ORMException;
use Doctrine\ORM\Id\AssignedGenerator;
use Doctrine\ORM\Mapping\Column;
use Doctrine\ORM\Mapping\Entity;
use Doctrine\ORM\Mapping\Id;
use Doctrine\Tests\OrmTestCase;
use PHPUnit\Framework\Attributes\DataProvider;

/**
 * AssignedGeneratorTest
 */
class AssignedGeneratorTest extends OrmTestCase
{
    private EntityManagerInterface $entityManager;

    private AssignedGenerator $assignedGen;

    protected function setUp(): void
    {
        $this->entityManager = $this->getTestEntityManager();
        $this->assignedGen   = new AssignedGenerator();
    }

    #[DataProvider('entitiesWithoutId')]
    public function testThrowsExceptionIfIdNotAssigned($entity): void
    {
        $this->expectException(ORMException::class);

        $this->assignedGen->generateId($this->entityManager, $entity);
    }

    public static function entitiesWithoutId(): array
    {
        return [
            'single'    => [new AssignedSingleIdEntity()],
            'composite' => [new AssignedCompositeIdEntity()],
        ];
    }

    public function testCorrectIdGeneration(): void
    {
        $entity       = new AssignedSingleIdEntity();
        $entity->myId = 1;
        $id           = $this->assignedGen->generateId($this->entityManager, $entity);
        self::assertEquals(['myId' => 1], $id);

        $entity        = new AssignedCompositeIdEntity();
        $entity->myId2 = 2;
        $entity->myId1 = 4;
        $id            = $this->assignedGen->generateId($this->entityManager, $entity);
        self::assertEquals(['myId1' => 4, 'myId2' => 2], $id);
    }
}

#[Entity]
class AssignedSingleIdEntity
{
    /** @var int */
    #[Id]
    #[Column(type: 'integer')]
    public $myId;
}

#[Entity]
class AssignedCompositeIdEntity
{
    /** @var int */
    #[Id]
    #[Column(type: 'integer')]
    public $myId1;

    /** @var int */
    #[Id]
    #[Column(type: 'integer')]
    public $myId2;
}
