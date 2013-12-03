<?php

namespace Doctrine\Tests\Models\DDC2645;

use Doctrine\Common\Collections\ArrayCollection;


/**
 * @Entity
 * @Table(name="ddc2645_variant")
 */
class DDC2645Variant
{
    /**
     * @Column(type="string")
     * @Id
     */
    private $id;

    /**
     * @Column(type="string", length=50)
     */
    private $name;

    /**
     * @ORM\OneToMany(targetEntity="DDC2645Price", mappedBy="variant", cascade={"all"})
     */
    private $prices;

    public function __construct()
    {
        $this->prices = new ArrayCollection();
    }

    /**
     * @param mixed $prices
     */
    public function setPrices($prices)
    {
        $this->prices = $prices;
    }

    public function addPrice($price)
    {
        $price->setVariant($this);

        $this->getPrices()->add($price);
    }

    /**
     * @return mixed
     */
    public function getPrices()
    {
        return $this->prices;
    }

    /**
     * @param mixed $id
     */
    public function setId($id)
    {
        $this->id = $id;
    }

    /**
     * @return mixed
     */
    public function getId()
    {
        return $this->id;
    }

    /**
     * @param mixed $name
     */
    public function setName($name)
    {
        $this->name = $name;
    }

    /**
     * @return mixed
     */
    public function getName()
    {
        return $this->name;
    }
}
