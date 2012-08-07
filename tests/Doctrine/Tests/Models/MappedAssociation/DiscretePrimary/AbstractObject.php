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
     *
     * @var int $id
     */
    private $id;

    /**
     * @OneToOne(targetEntity="Shelf", mappedBy="object")
     *
     * @var Shelf $shelf
     */
    private $shelf;

    /**
     * @Column(type="string", length=128)
     *
     * @var string $description
     */
    private $description;

    /**
     * @return int
     */
    public function getId()
    {
        return $this->id;
    }

    /**
     * @param string $description
     */
    public function setDescription($description)
    {
        $this->description = $description;
    }

    /**
     * @return string
     */
    public function getDescription()
    {
        return $this->description;
    }

    /**
     * @return Shelf
     */
    public function getShelf()
    {
        return $this->shelf;
    }

    /**
     * @param Shelf $shelf
     */
    public function setShelf(Shelf $shelf)
    {
        $this->shelf = $shelf;
    }
}
