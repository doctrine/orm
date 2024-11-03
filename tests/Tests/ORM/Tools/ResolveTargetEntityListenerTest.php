<?php

declare(strict_types=1);

namespace Doctrine\Tests\ORM\Tools;

use Doctrine\Common\Collections\Collection;
use Doctrine\ORM\EntityManagerInterface;
use Doctrine\ORM\Events;
use Doctrine\ORM\Mapping\ClassMetadataFactory;
use Doctrine\ORM\Mapping\Column;
use Doctrine\ORM\Mapping\Entity;
use Doctrine\ORM\Mapping\GeneratedValue;
use Doctrine\ORM\Mapping\Id;
use Doctrine\ORM\Mapping\JoinColumn;
use Doctrine\ORM\Mapping\ManyToMany;
use Doctrine\ORM\Mapping\ManyToOne;
use Doctrine\ORM\Mapping\OneToMany;
use Doctrine\ORM\Mapping\OneToOne;
use Doctrine\ORM\Tools\ResolveTargetEntityListener;
use Doctrine\Tests\OrmTestCase;
use PHPUnit\Framework\Attributes\CoversNothing;
use PHPUnit\Framework\Attributes\Group;

class ResolveTargetEntityListenerTest extends OrmTestCase
{
    private EntityManagerInterface $em;

    private ResolveTargetEntityListener $listener;

    private ClassMetadataFactory $factory;

    protected function setUp(): void
    {
        $this->em = $this->getTestEntityManager();
        $this->em->getConfiguration()->setMetadataDriverImpl($this->createAttributeDriver());
        $this->factory  = $this->em->getMetadataFactory();
        $this->listener = new ResolveTargetEntityListener();
    }

    #[Group('DDC-1544')]
    public function testResolveTargetEntityListenerCanResolveTargetEntity(): void
    {
        $evm = $this->em->getEventManager();
        $this->listener->addResolveTargetEntity(ResolveTarget::class, ResolveTargetEntity::class, []);
        $this->listener->addResolveTargetEntity(Target::class, TargetEntity::class, []);
        $evm->addEventSubscriber($this->listener);

        $cm   = $this->factory->getMetadataFor(ResolveTargetEntity::class);
        $meta = $cm->associationMappings;

        self::assertSame(TargetEntity::class, $meta['manyToMany']->targetEntity);
        self::assertSame(ResolveTargetEntity::class, $meta['manyToOne']->targetEntity);
        self::assertSame(ResolveTargetEntity::class, $meta['oneToMany']->targetEntity);
        self::assertSame(TargetEntity::class, $meta['oneToOne']->targetEntity);

        self::assertSame($cm, $this->factory->getMetadataFor(ResolveTarget::class));
    }

    #[Group('DDC-3385')]
    #[Group('1181')]
    #[Group('385')]
    public function testResolveTargetEntityListenerCanRetrieveTargetEntityByInterfaceName(): void
    {
        $this->listener->addResolveTargetEntity(ResolveTarget::class, ResolveTargetEntity::class, []);

        $this->em->getEventManager()->addEventSubscriber($this->listener);

        $cm = $this->factory->getMetadataFor(ResolveTarget::class);

        self::assertSame($this->factory->getMetadataFor(ResolveTargetEntity::class), $cm);
    }

    #[Group('DDC-2109')]
    public function testAssertTableColumnsAreNotAddedInManyToMany(): void
    {
        $evm = $this->em->getEventManager();
        $this->listener->addResolveTargetEntity(ResolveTarget::class, ResolveTargetEntity::class, []);
        $this->listener->addResolveTargetEntity(Target::class, TargetEntity::class, []);

        $evm->addEventListener(Events::loadClassMetadata, $this->listener);
        $cm   = $this->factory->getMetadataFor(ResolveTargetEntity::class);
        $meta = $cm->associationMappings['manyToMany'];

        self::assertSame(TargetEntity::class, $meta->targetEntity);
        self::assertEquals(['resolvetargetentity_id', 'target_id'], $meta->joinTableColumns);
    }

    #[CoversNothing]
    #[Group('1572')]
    #[Group('functional')]
    public function testDoesResolveTargetEntitiesInDQLAlsoWithInterfaces(): void
    {
        $evm = $this->em->getEventManager();
        $this->listener->addResolveTargetEntity(ResolveTarget::class, ResolveTargetEntity::class, []);

        $evm->addEventSubscriber($this->listener);

        self::assertStringMatchesFormat(
            'SELECT%AFROM ResolveTargetEntity%A',
            $this
                ->em
                ->createQuery('SELECT f FROM Doctrine\Tests\ORM\Tools\ResolveTarget f')
                ->getSQL(),
        );
    }
}

interface ResolveTarget
{
    public function getId(): int;
}

interface Target extends ResolveTarget
{
}

#[Entity]
class ResolveTargetEntity implements ResolveTarget
{
    #[Id]
    #[Column(type: 'integer')]
    #[GeneratedValue(strategy: 'AUTO')]
    private int $id;

    /** @phpstan-var Collection<int, Target> */
    #[ManyToMany(targetEntity: 'Doctrine\Tests\ORM\Tools\Target')]
    private $manyToMany;

    #[ManyToOne(targetEntity: 'Doctrine\Tests\ORM\Tools\ResolveTarget', inversedBy: 'oneToMany')]
    private ResolveTarget $manyToOne;

    /** @phpstan-var Collection<int, ResolveTarget> */
    #[OneToMany(targetEntity: 'Doctrine\Tests\ORM\Tools\ResolveTarget', mappedBy: 'manyToOne')]
    private $oneToMany;

    #[OneToOne(targetEntity: 'Doctrine\Tests\ORM\Tools\Target')]
    #[JoinColumn(name: 'target_entity_id', referencedColumnName: 'id')]
    private Target $oneToOne;

    public function getId(): int
    {
        return $this->id;
    }
}

#[Entity]
class TargetEntity implements Target
{
    #[Id]
    #[Column(type: 'integer')]
    #[GeneratedValue(strategy: 'AUTO')]
    private int $id;

    public function getId(): int
    {
        return $this->id;
    }
}
