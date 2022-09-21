<?php

declare(strict_types=1);

namespace Doctrine\Tests\Models\PersistentObject;

use Doctrine\Common\Collections\ArrayCollection;
use Doctrine\Common\Collections\Collection;
use Doctrine\Common\Persistence\PersistentObject;
use Doctrine\ORM\Mapping\Column;
use Doctrine\ORM\Mapping\Entity;
use Doctrine\ORM\Mapping\GeneratedValue;
use Doctrine\ORM\Mapping\Id;
use Doctrine\ORM\Mapping\ManyToMany;

/** @Entity */
class PersistentCollectionHolder extends PersistentObject
{
    /**
     * @Id
     * @Column(type="integer")
     * @GeneratedValue
     * @var int
     */
    protected $id;

    /**
     * @var Collection
     * @ManyToMany(targetEntity="PersistentCollectionContent", cascade={"all"}, fetch="EXTRA_LAZY")
     */
    protected $collection;

    public function __construct()
    {
        $this->collection = new ArrayCollection();
    }

    public function addElement(PersistentCollectionContent $element): void
    {
        $this->collection->add($element);
    }

    /** @return Collection */
    public function getCollection(): Collection
    {
        return clone $this->collection;
    }

    /** @return Collection */
    public function getRawCollection(): Collection
    {
        return $this->collection;
    }
}
