<?php

declare(strict_types=1);

namespace Doctrine\Tests\ORM\Functional\Ticket;

use Doctrine\ORM\Mapping\ChangeTrackingPolicy;
use Doctrine\ORM\Mapping\Column;
use Doctrine\ORM\Mapping\Entity;
use Doctrine\ORM\Mapping\GeneratedValue;
use Doctrine\ORM\Mapping\Id;
use Doctrine\ORM\Mapping\OneToOne;
use Doctrine\Persistence\NotifyPropertyChanged;
use Doctrine\Persistence\PropertyChangedListener;
use Doctrine\Persistence\Proxy;
use Doctrine\Tests\OrmFunctionalTestCase;

use function count;
use function in_array;

class DDC1690Test extends OrmFunctionalTestCase
{
    protected function setUp(): void
    {
        parent::setUp();

        $this->createSchemaForModels(DDC1690Parent::class, DDC1690Child::class);
    }

    public function testChangeTracking(): void
    {
        $parent = new DDC1690Parent();
        $child  = new DDC1690Child();
        $parent->setName('parent');
        $child->setName('child');

        $parent->setChild($child);
        $child->setParent($parent);

        $this->_em->persist($parent);
        $this->_em->persist($child);

        self::assertEquals(1, count($parent->listeners));
        self::assertEquals(1, count($child->listeners));

        $this->_em->flush();
        $this->_em->clear();

        self::assertEquals(1, count($parent->listeners));
        self::assertEquals(1, count($child->listeners));

        $parentId = $parent->getId();
        $childId  = $child->getId();
        unset($parent, $child);

        $parent = $this->_em->find(DDC1690Parent::class, $parentId);
        $child  = $this->_em->find(DDC1690Child::class, $childId);

        self::assertEquals(1, count($parent->listeners));
        self::assertInstanceOf(Proxy::class, $child, 'Verifying that $child is a proxy before using proxy API');
        self::assertCount(0, $child->listeners);
        $child->__load();
        self::assertCount(1, $child->listeners);
        unset($parent, $child);

        $parent = $this->_em->find(DDC1690Parent::class, $parentId);
        $child  = $parent->getChild();

        self::assertEquals(1, count($parent->listeners));
        self::assertEquals(1, count($child->listeners));
        unset($parent, $child);

        $child  = $this->_em->find(DDC1690Child::class, $childId);
        $parent = $child->getParent();

        self::assertEquals(1, count($parent->listeners));
        self::assertEquals(1, count($child->listeners));
    }
}

class NotifyBaseEntity implements NotifyPropertyChanged
{
    /** @psalm-var list<PropertyChangedListener> */
    public $listeners = [];

    public function addPropertyChangedListener(PropertyChangedListener $listener): void
    {
        if (! in_array($listener, $this->listeners, true)) {
            $this->listeners[] = $listener;
        }
    }

    protected function onPropertyChanged($propName, $oldValue, $newValue): void
    {
        if ($this->listeners) {
            foreach ($this->listeners as $listener) {
                $listener->propertyChanged($this, $propName, $oldValue, $newValue);
            }
        }
    }
}

/**
 * @Entity
 * @ChangeTrackingPolicy("NOTIFY")
 */
class DDC1690Parent extends NotifyBaseEntity
{
    /**
     * @var int
     * @Id
     * @Column(type="integer")
     * @GeneratedValue
     */
    private $id;

    /**
     * @var string
     * @Column
     */
    private $name;

    /**
     * @var DDC1690Child
     * @OneToOne(targetEntity="DDC1690Child")
     */
    private $child;

    public function getId(): int
    {
        return $this->id;
    }

    public function getName(): string
    {
        return $this->name;
    }

    public function setName(string $name): void
    {
        $this->onPropertyChanged('name', $this->name, $name);
        $this->name = $name;
    }

    public function setChild(DDC1690Child $child): void
    {
        $this->child = $child;
    }

    public function getChild(): DDC1690Child
    {
        return $this->child;
    }
}

/** @Entity */
class DDC1690Child extends NotifyBaseEntity
{
    /**
     * @var int
     * @Id
     * @Column(type="integer")
     * @GeneratedValue
     */
    private $id;

    /**
     * @var string
     * @Column
     */
    private $name;

    /**
     * @var DDC1690Parent
     * @OneToOne(targetEntity="DDC1690Parent", mappedBy="child")
     */
    private $parent;

    public function getId(): int
    {
        return $this->id;
    }

    public function getName(): string
    {
        return $this->name;
    }

    public function setName(string $name): void
    {
        $this->onPropertyChanged('name', $this->name, $name);
        $this->name = $name;
    }

    public function setParent(DDC1690Parent $parent): void
    {
        $this->parent = $parent;
    }

    public function getParent(): DDC1690Parent
    {
        return $this->parent;
    }
}
