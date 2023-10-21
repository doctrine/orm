<?php

declare(strict_types=1);

namespace Doctrine\Tests\Models\CollectionWithInheritance;

use Doctrine\Common\Collections\ArrayCollection;
use Doctrine\Common\Collections\Collection;
use Doctrine\ORM\Mapping as ORM;
use Doctrine\ORM\Mapping\Column;
use Doctrine\ORM\Mapping\Entity;
use Doctrine\ORM\Mapping\GeneratedValue;
use Doctrine\ORM\Mapping\Id;
use Doctrine\ORM\Mapping\Table;

/**
 * @Entity
 * @Table(name="collection_with_inheritance_pet_store")
 */
class PetStore
{
    /**
     * @var int
     * @Id
     * @Column(type="integer")
     * @GeneratedValue(strategy="AUTO")
     */
    public $id;

    /**
     * @ORM\OneToMany(targetEntity="Dog", mappedBy="petStore", cascade={"persist", "remove"}, orphanRemoval=true)
     *
     * @var Collection<int, Dog>
     */
    private $dogs;

    /**
     * @ORM\OneToMany(targetEntity="Cat", mappedBy="petStore", cascade={"persist", "remove"}, orphanRemoval=true)
     *
     * @var Collection<int, Cat>
     */
    private $cats;

    public function __construct()
    {
        $this->cats = new ArrayCollection();
        $this->dogs = new ArrayCollection();
    }

    public function getDogs(): array
    {
        return $this->dogs->toArray();
    }

    public function setDogs(array $dogs): void
    {
        $this->dogs = new ArrayCollection($dogs);
    }

    public function getCats(): array
    {
        return $this->cats->toArray();
    }

    public function setCats(array $cats): void
    {
        $this->cats = new ArrayCollection($cats);
    }
}
