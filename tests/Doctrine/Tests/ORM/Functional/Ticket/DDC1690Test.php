<?php

declare(strict_types=1);

namespace Doctrine\Tests\ORM\Functional\Ticket;

use Doctrine\Common\NotifyPropertyChanged;
use Doctrine\Common\PropertyChangedListener;
use Doctrine\ORM\Annotation as ORM;
use Doctrine\Tests\OrmFunctionalTestCase;
use Exception;
use ProxyManager\Proxy\GhostObjectInterface;
use function in_array;

class DDC1690Test extends OrmFunctionalTestCase
{
    protected function setUp() : void
    {
        parent::setUp();
        try {
            $this->schemaTool->createSchema(
                [
                    $this->em->getClassMetadata(DDC1690Parent::class),
                    $this->em->getClassMetadata(DDC1690Child::class),
                ]
            );
        } catch (Exception $e) {
            // Swallow all exceptions. We do not test the schema tool here.
        }
    }

    public function testChangeTracking() : void
    {
        $parent = new DDC1690Parent();
        $child  = new DDC1690Child();
        $parent->setName('parent');
        $child->setName('child');

        $parent->setChild($child);
        $child->setParent($parent);

        $this->em->persist($parent);
        $this->em->persist($child);

        self::assertCount(1, $parent->listeners);
        self::assertCount(1, $child->listeners);

        $this->em->flush();
        $this->em->clear();

        self::assertCount(1, $parent->listeners);
        self::assertCount(1, $child->listeners);

        $parentId = $parent->getId();
        $childId  = $child->getId();

        $fetchedParent = $this->em->find(DDC1690Parent::class, $parentId);
        /** @var DDC1690Child|GhostObjectInterface $fetchedChild */
        $fetchedChild = $this->em->find(DDC1690Child::class, $childId);

        self::assertCount(1, $fetchedParent->listeners);
        self::assertInstanceOf(GhostObjectInterface::class, $fetchedChild, 'Verifying that $child is a proxy before using proxy API');
        self::assertFalse($fetchedChild->isProxyInitialized());
        self::assertCount(1, $fetchedChild->listeners);

        $fetchedChild->initializeProxy();

        self::assertCount(1, $fetchedChild->listeners);

        $secondFetchedParent = $this->em->find(DDC1690Parent::class, $parentId);
        $secondFetchedChild  = $parent->getChild();

        self::assertCount(1, $secondFetchedParent->listeners);
        self::assertCount(1, $secondFetchedChild->listeners);

        $thirdFetchedChild  = $this->em->find(DDC1690Child::class, $childId);
        $thirdFetchedParent = $child->getParent();

        self::assertCount(1, $thirdFetchedParent->listeners);
        self::assertCount(1, $thirdFetchedChild->listeners);
    }
}

class NotifyBaseEntity implements NotifyPropertyChanged
{
    public $listeners = [];

    public function addPropertyChangedListener(PropertyChangedListener $listener)
    {
        if (! in_array($listener, $this->listeners, true)) {
            $this->listeners[] = $listener;
        }
    }

    protected function onPropertyChanged($propName, $oldValue, $newValue)
    {
        foreach ($this->listeners as $listener) {
            $listener->propertyChanged($this, $propName, $oldValue, $newValue);
        }
    }
}

/** @ORM\Entity @ORM\ChangeTrackingPolicy("NOTIFY") */
class DDC1690Parent extends NotifyBaseEntity
{
    /** @ORM\Id @ORM\Column(type="integer") @ORM\GeneratedValue */
    private $id;

    /** @ORM\Column */
    private $name;

    /** @ORM\OneToOne(targetEntity=DDC1690Child::class) */
    private $child;

    public function getId()
    {
        return $this->id;
    }

    public function getName()
    {
        return $this->name;
    }

    public function setName($name)
    {
        $this->onPropertyChanged('name', $this->name, $name);
        $this->name = $name;
    }

    public function setChild($child)
    {
        $this->child = $child;
    }

    public function getChild()
    {
        return $this->child;
    }
}

/** @ORM\Entity */
class DDC1690Child extends NotifyBaseEntity
{
    /** @ORM\Id @ORM\Column(type="integer") @ORM\GeneratedValue */
    private $id;

    /** @ORM\Column */
    private $name;

    /** @ORM\OneToOne(targetEntity=DDC1690Parent::class, mappedBy="child") */
    private $parent;

    public function getId()
    {
        return $this->id;
    }

    public function getName()
    {
        return $this->name;
    }

    public function setName($name)
    {
        $this->onPropertyChanged('name', $this->name, $name);
        $this->name = $name;
    }

    public function setParent($parent)
    {
        $this->parent = $parent;
    }

    public function getParent()
    {
        return $this->parent;
    }
}
