<?php
namespace Doctrine\Tests\Models\MappedAssociation\DiscretePrimary;

/**
 * @MappedSuperclass
 */
class AbstractObject
{
    /**
     * @Id
     * @GeneratedValue
     * @Column(type="integer")
     */
    private $id;

    /**
     * @OneToOne(targetEntity="Shelf", mappedBy="object")
     */
    private $shelf;

    /**
     * @Column(type="string", length=128)
     */
    private $description;

    public function getId()
    {
        return $this->id;
    }

    public function setDescription($description)
    {
        $this->description = $description;
    }

    public function getDescription()
    {
        return $this->description;
    }

    public function getShelf()
    {
        return $this->shelf;
    }

    public function setShelf(Shelf $shelf)
    {
        $this->shelf = $shelf;
    }
}
