<?php

declare(strict_types=1);

namespace Doctrine\Tests\ORM\Functional;

use Doctrine\Common\Collections\ArrayCollection;
use Doctrine\ORM\Mapping\Column;
use Doctrine\ORM\Mapping\Entity;
use Doctrine\ORM\Mapping\GeneratedValue;
use Doctrine\ORM\Mapping\Id;
use Doctrine\ORM\Mapping\JoinColumn;
use Doctrine\ORM\Mapping\ManyToOne;
use Doctrine\ORM\Mapping\OneToMany;
use Doctrine\ORM\Mapping\OneToOne;
use Doctrine\Tests\OrmFunctionalTestCase;
use PHPUnit\Framework\Attributes\Group;

#[Group('CascadeRemoveOrderTest')]
class CascadeRemoveOrderTest extends OrmFunctionalTestCase
{
    protected function setUp(): void
    {
        parent::setUp();

        $this->createSchemaForModels(
            CascadeRemoveOrderEntityO::class,
            CascadeRemoveOrderEntityG::class,
        );
    }

    public function testSingle(): void
    {
        $eO = new CascadeRemoveOrderEntityO();
        $eG = new CascadeRemoveOrderEntityG($eO);

        $this->_em->persist($eO);
        $this->_em->flush();
        $this->_em->clear();

        $eOloaded = $this->_em->find(CascadeRemoveOrderEntityO::class, $eO->getId());

        $this->_em->remove($eOloaded);
        $this->_em->flush();

        self::assertNull($this->_em->find(CascadeRemoveOrderEntityG::class, $eG->getId()));
    }

    public function testMany(): void
    {
        $eO  = new CascadeRemoveOrderEntityO();
        $eG1 = new CascadeRemoveOrderEntityG($eO);
        $eG2 = new CascadeRemoveOrderEntityG($eO);
        $eG3 = new CascadeRemoveOrderEntityG($eO);

        $eO->setOneToOneG($eG2);

        $this->_em->persist($eO);
        $this->_em->flush();
        $this->_em->clear();

        $eOloaded = $this->_em->find(CascadeRemoveOrderEntityO::class, $eO->getId());

        $this->_em->remove($eOloaded);
        $this->_em->flush();

        self::assertNull($this->_em->find(CascadeRemoveOrderEntityG::class, $eG1->getId()));
        self::assertNull($this->_em->find(CascadeRemoveOrderEntityG::class, $eG2->getId()));
        self::assertNull($this->_em->find(CascadeRemoveOrderEntityG::class, $eG3->getId()));
    }
}

#[Entity]
class CascadeRemoveOrderEntityO
{
    #[Id]
    #[Column(type: 'integer')]
    #[GeneratedValue]
    private int $id;

    #[OneToOne(targetEntity: 'Doctrine\Tests\ORM\Functional\CascadeRemoveOrderEntityG')]
    #[JoinColumn(nullable: true, onDelete: 'SET NULL')]
    private CascadeRemoveOrderEntityG|null $oneToOneG = null;

    /** @phpstan-var Collection<int, CascadeRemoveOrderEntityG> */
    #[OneToMany(targetEntity: 'Doctrine\Tests\ORM\Functional\CascadeRemoveOrderEntityG', mappedBy: 'ownerO', cascade: ['persist', 'remove'])]
    private $oneToManyG;

    public function __construct()
    {
        $this->oneToManyG = new ArrayCollection();
    }

    public function getId(): int
    {
        return $this->id;
    }

    public function setOneToOneG(CascadeRemoveOrderEntityG $eG): void
    {
        $this->oneToOneG = $eG;
    }

    public function getOneToOneG(): CascadeRemoveOrderEntityG
    {
        return $this->oneToOneG;
    }

    public function addOneToManyG(CascadeRemoveOrderEntityG $eG): void
    {
        $this->oneToManyG->add($eG);
    }

    /** @phpstan-return array<int, CascadeRemoveOrderEntityG> */
    public function getOneToManyGs(): array
    {
        return $this->oneToManyG->toArray();
    }
}

#[Entity]
class CascadeRemoveOrderEntityG
{
    #[Id]
    #[Column(type: 'integer')]
    #[GeneratedValue]
    private int $id;

    public function __construct(
        #[ManyToOne(targetEntity: 'Doctrine\Tests\ORM\Functional\CascadeRemoveOrderEntityO', inversedBy: 'oneToMany')]
        private CascadeRemoveOrderEntityO $ownerO,
        private int $position = 1,
    ) {
        $this->ownerO->addOneToManyG($this);
    }

    public function getId(): int
    {
        return $this->id;
    }
}
