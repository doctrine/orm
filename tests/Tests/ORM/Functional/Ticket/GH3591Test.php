<?php

declare(strict_types=1);

namespace Doctrine\Tests\ORM\Functional\Ticket;

use Doctrine\Common\Collections\ArrayCollection;
use Doctrine\Common\Collections\Collection;
use Doctrine\Common\Collections\Criteria;
use Doctrine\Common\Collections\Expr\Comparison;
use Doctrine\ORM\Mapping as ORM;
use Doctrine\Tests\OrmFunctionalTestCase;
use Generator;

class GH3591Test extends OrmFunctionalTestCase
{
    protected function setUp(): void
    {
        parent::setUp();

        $this->setUpEntitySchema([
            GH3591Entity::class,
            GH3591CollectionEntry::class,
        ]);
    }

    /**
     * @dataProvider provideFieldNamesAndInitializationState
     */
    public function testMatchingApiOnUninitializedCollection(string $fieldName): void
    {
        $criteria = $this->createComparisonCriteria($fieldName, 'test value');
        $entity   = $this->createAndLoadEntityWithCollectionEntry('test value');

        $matches = $entity->entries->matching($criteria);

        self::assertCount(1, $matches);
    }

    /**
     * @dataProvider provideFieldNamesAndInitializationState
     */
    public function testMatchingApiOnInitializedCollection(string $fieldName): void
    {
        $criteria = $this->createComparisonCriteria($fieldName, 'test value');
        $entity   = $this->createAndLoadEntityWithCollectionEntry('test value');
        $entity->entries->initialize();

        $matches = $entity->entries->matching($criteria);

        self::assertCount(1, $matches);
    }

    /**
     * @dataProvider provideFieldNamesAndInitializationState
     */
    public function testMatchingApiOnUnpersistedCollection(string $fieldName): void
    {
        $criteria = $this->createComparisonCriteria($fieldName, 'test value');
        $entity = new GH3591Entity();
        $entry  = new GH3591CollectionEntry('test value');
        $entity->addEntry($entry);

        $matches = $entity->entries->matching($criteria);

        self::assertCount(1, $matches);
    }

    /**
     * @return Generator<string>
     */
    public function provideFieldNamesAndInitializationState(): Generator
    {
        yield ['privateField'];
        yield ['protectedField'];
        yield ['publicField'];
        yield ['fieldWithAwkwardGetter'];
        yield ['duplicatePrivateField'];
    }

    private function createAndLoadEntityWithCollectionEntry(string $entryFieldValue): GH3591Entity
    {
        $entity = new GH3591Entity();
        $entry  = new GH3591CollectionEntry($entryFieldValue);

        $entity->addEntry($entry);
        $this->_em->persist($entity);
        $this->_em->flush();
        $this->_em->clear();

        return $this->_em->find(GH3591Entity::class, $entity->id);
    }

    private function createComparisonCriteria(string $fieldName, string $expectedValue): Criteria
    {
        $expr     = new Comparison($fieldName, '=', $expectedValue);
        $criteria = new Criteria();
        $criteria->where($expr);

        return $criteria;
    }
}

/**
 * @ORM\Entity()
 */
class GH3591Entity
{
    /**
     * @ORM\Id
     * @ORM\Column(type="integer")
     * @ORM\GeneratedValue
     *
     * @var int
     */
    public $id;

    /**
     * @ORM\OneToMany(targetEntity="GH3591CollectionEntry", mappedBy="entity", cascade={"persist"})
     *
     * @var Collection<int, GH3591CollectionEntry>
     */
    public $entries;

    public function __construct()
    {
        $this->entries = new ArrayCollection();
    }

    public function addEntry(GH3591CollectionEntry $child): void
    {
        if (! $this->entries->contains($child)) {
            $this->entries->add($child);
            $child->setEntity($this);
        }
    }
}

/**
 * @ORM\MappedSuperclass()
 */
class GH3591CollectionEntrySuperclass
{
    /**
     * @ORM\Column(type="string")
     *
     * @var string
     */
    private $duplicatePrivateField;

    public function __construct(string $duplicatePrivateField)
    {
        $this->duplicatePrivateField = $duplicatePrivateField;
    }
}

/**
 * @ORM\Entity()
 */
class GH3591CollectionEntry extends GH3591CollectionEntrySuperclass
{
    /**
     * @ORM\Id
     * @ORM\Column(type="integer")
     * @ORM\GeneratedValue
     *
     * @var int
     */
    private $id;

    /**
     * @ORM\ManyToOne(targetEntity="GH3591Entity", inversedBy="entries")
     *
     * @var GH3591Entity
     */
    private $entity;

    /**
     * @ORM\Column(type="string")
     *
     * @var string
     */
    private $privateField;

    /**
     * @ORM\Column(type="string")
     *
     * @var string
     */
    protected $protectedField;

    /**
     * @ORM\Column(type="string")
     *
     * @var string
     */
    public $publicField;

    /**
     * @ORM\Column(type="string")
     *
     * @var string
     */
    public $fieldWithAwkwardGetter;

    private $duplicatePrivateField;

    public function __construct($value)
    {
        $this->privateField           = $value;
        $this->protectedField         = $value;
        $this->publicField            = $value;
        $this->fieldWithAwkwardGetter = $value;

        parent::__construct($value);
        $this->duplicatePrivateField = 'this is a transient field';
    }

    public function setEntity(GH3591Entity $entity): void
    {
        $this->entity = $entity;
        $entity->addEntry($this);
    }

    public function fieldWithAwkwardGetter(): string
    {
        return 'not what you expect' . $this->fieldWithAwkwardGetter;
    }
}
