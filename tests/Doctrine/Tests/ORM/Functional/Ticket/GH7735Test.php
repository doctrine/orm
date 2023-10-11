<?php

declare(strict_types=1);

namespace Doctrine\Tests\ORM\Functional\Ticket;

use Doctrine\ORM\Mapping\Cache;
use Doctrine\ORM\Mapping\Column;
use Doctrine\ORM\Mapping\Entity;
use Doctrine\ORM\Mapping\Id;
use Doctrine\ORM\Mapping\JoinColumn;
use Doctrine\ORM\Mapping\ManyToOne;
use Doctrine\ORM\Mapping\OneToOne;
use Doctrine\Tests\OrmFunctionalTestCase;
use PHPUnit\Framework\Attributes\Group;
use PHPUnit\Framework\Attributes\Test;

use function assert;

final class GH7735Test extends OrmFunctionalTestCase
{
    protected function setUp(): void
    {
        $this->enableSecondLevelCache();

        parent::setUp();

        $this->createSchemaForModels(
            GH7735Car::class,
            GH7735Power::class,
            GH7735Engine::class,
        );

        $this->_em->persist(new GH7735Car(1, new GH7735Engine(1, 'turbo', new GH7735Power(1))));
        $this->_em->flush();
        $this->_em->clear();
    }

    #[Test]
    #[Group('GH7735')]
    public function findByReturnsCachedEntity(): void
    {
        $this->_em->getCache()->evictEntityRegion(GH7735Power::class);

        $car = $this->_em->find(GH7735Car::class, 1);
        assert($car instanceof GH7735Car);

        self::assertSame('turbo', $car->getEngine()->getModel());
        self::assertSame(1, $car->getEngine()->getPower()->getId());
    }
}

#[Entity]
#[Cache(usage: 'READ_ONLY')]
class GH7735Car
{
    public function __construct(
        #[Id]
        #[Column(type: 'integer')]
        private int $id,
        #[ManyToOne(targetEntity: GH7735Engine::class, cascade: ['all'])]
        #[JoinColumn(nullable: false)]
        #[Cache('READ_ONLY')]
        private GH7735Engine $engine,
    ) {
    }

    public function getId(): int
    {
        return $this->id;
    }

    public function getEngine(): GH7735Engine
    {
        return $this->engine;
    }
}

#[Entity]
#[Cache(usage: 'READ_ONLY')]
class GH7735Engine
{
    public function __construct(
        #[Id]
        #[Column(type: 'integer')]
        private int $id,
        #[Column]
        private string $model,
        #[OneToOne(targetEntity: GH7735Power::class, mappedBy: 'engine', cascade: ['all'])]
        #[Cache('READ_ONLY')]
        private GH7735Power $power,
    ) {
        $power->setEngine($this);
    }

    public function getId(): int
    {
        return $this->id;
    }

    public function getPower(): GH7735Power
    {
        return $this->power;
    }

    public function getModel(): string
    {
        return $this->model;
    }
}

#[Entity]
#[Cache(usage: 'READ_ONLY')]
class GH7735Power
{
    #[OneToOne(targetEntity: GH7735Engine::class, inversedBy: 'power')]
    #[Cache('READ_ONLY')]
    private GH7735Engine|null $engine = null;

    public function __construct(
        #[Id]
        #[Column(type: 'integer')]
        private int $id,
    ) {
    }

    public function getId(): int
    {
        return $this->id;
    }

    public function setEngine(GH7735Engine $engine): void
    {
        $this->engine = $engine;
    }

    public function getEngine(): GH7735Engine
    {
        return $this->engine;
    }
}
