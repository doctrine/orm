<?php

declare(strict_types=1);

namespace Doctrine\Tests\ORM\Functional;

use Doctrine\Common\Collections\ArrayCollection;
use Doctrine\Common\Collections\Collection;
use Doctrine\ORM\Mapping\Column;
use Doctrine\ORM\Mapping\DiscriminatorColumn;
use Doctrine\ORM\Mapping\DiscriminatorMap;
use Doctrine\ORM\Mapping\Entity;
use Doctrine\ORM\Mapping\GeneratedValue;
use Doctrine\ORM\Mapping\Id;
use Doctrine\ORM\Mapping\InheritanceType;
use Doctrine\ORM\Mapping\JoinColumn;
use Doctrine\ORM\Mapping\ManyToMany;
use Doctrine\ORM\Mapping\OneToOne;
use Doctrine\ORM\Mapping\Table;
use Doctrine\Tests\OrmFunctionalTestCase;

use function count;

/**
 * Functional tests for the Class Table Inheritance mapping strategy.
 */
class ClassTableInheritanceSecondTest extends OrmFunctionalTestCase
{
    protected function setUp(): void
    {
        parent::setUp();

        $this->createSchemaForModels(
            CTIParent::class,
            CTIChild::class,
            CTIRelated::class,
            CTIRelated2::class,
        );
    }

    public function testOneToOneAssocToBaseTypeBidirectional(): void
    {
        $child = new CTIChild();
        $child->setData('hello');

        $related = new CTIRelated();
        $related->setCTIParent($child);

        $this->_em->persist($related);
        $this->_em->persist($child);

        $this->_em->flush();
        $this->_em->clear();

        $relatedId = $related->getId();

        $related2 = $this->_em->find(CTIRelated::class, $relatedId);

        self::assertInstanceOf(CTIRelated::class, $related2);
        self::assertInstanceOf(CTIChild::class, $related2->getCTIParent());
        self::assertEquals('hello', $related2->getCTIParent()->getData());

        self::assertSame($related2, $related2->getCTIParent()->getRelated());
    }

    public function testManyToManyToCTIHierarchy(): void
    {
        $mmrel = new CTIRelated2();
        $child = new CTIChild();
        $child->setData('child');
        $mmrel->addCTIChild($child);

        $this->_em->persist($mmrel);
        $this->_em->persist($child);

        $this->_em->flush();
        $this->_em->clear();

        $mmrel2 = $this->_em->find($mmrel::class, $mmrel->getId());
        self::assertFalse($mmrel2->getCTIChildren()->isInitialized());
        self::assertEquals(1, count($mmrel2->getCTIChildren()));
        self::assertTrue($mmrel2->getCTIChildren()->isInitialized());
        self::assertInstanceOf(CTIChild::class, $mmrel2->getCTIChildren()->get(0));
    }
}

#[Table(name: 'cti_parents')]
#[Entity]
#[InheritanceType('JOINED')]
#[DiscriminatorColumn(name: 'type', type: 'string')]
#[DiscriminatorMap(['parent' => 'CTIParent', 'child' => 'CTIChild'])]
class CTIParent
{
    #[Id]
    #[Column(type: 'integer')]
    #[GeneratedValue(strategy: 'AUTO')]
    private int $id;

    #[OneToOne(targetEntity: 'CTIRelated', mappedBy: 'ctiParent')]
    private CTIRelated|null $related = null;

    public function getId(): int
    {
        return $this->id;
    }

    public function getRelated(): CTIRelated
    {
        return $this->related;
    }

    public function setRelated(CTIRelated $related): void
    {
        $this->related = $related;
        $related->setCTIParent($this);
    }
}

#[Table(name: 'cti_children')]
#[Entity]
class CTIChild extends CTIParent
{
    #[Column(type: 'string', length: 255)]
    private string|null $data = null;

    public function getData(): string
    {
        return $this->data;
    }

    public function setData(string $data): void
    {
        $this->data = $data;
    }
}

#[Entity]
class CTIRelated
{
    #[Id]
    #[Column(type: 'integer')]
    #[GeneratedValue(strategy: 'AUTO')]
    private int $id;

    #[OneToOne(targetEntity: 'CTIParent')]
    #[JoinColumn(name: 'ctiparent_id', referencedColumnName: 'id')]
    private CTIParent|null $ctiParent = null;

    public function getId(): int
    {
        return $this->id;
    }

    public function getCTIParent(): CTIParent
    {
        return $this->ctiParent;
    }

    public function setCTIParent(CTIParent $ctiParent): void
    {
        $this->ctiParent = $ctiParent;
    }
}

#[Entity]
class CTIRelated2
{
    #[Id]
    #[Column(type: 'integer')]
    #[GeneratedValue]
    private int $id;

    /** @phpstan-var Collection<int, CTIChild> */
    #[ManyToMany(targetEntity: 'CTIChild')]
    private $ctiChildren;

    public function __construct()
    {
        $this->ctiChildren = new ArrayCollection();
    }

    public function getId(): int
    {
        return $this->id;
    }

    public function addCTIChild(CTIChild $child): void
    {
        $this->ctiChildren->add($child);
    }

    /** @phpstan-return Collection<int, CTIChild> */
    public function getCTIChildren(): Collection
    {
        return $this->ctiChildren;
    }
}
