<?php

namespace Doctrine\Tests\Models\DDC1925;

use Doctrine\Common\Collections\ArrayCollection;
use Doctrine\Tests\Models\DDC1925\DDC1925User;

/**
 * @Table
 * @Entity
 */
class DDC1925Product
{
    /**
     * @var integer $id
     *
     * @Column(name="id", type="integer")
     * @Id
     * @GeneratedValue(strategy="AUTO")
     */
    private $id;

    /**
     * @var string $title
     *
     * @Column(name="title", type="string", length=255)
     */
    private $title;

    /**
     * @ManyToMany(targetEntity="Doctrine\Tests\Models\DDC1925\DDC1925User")
     * @JoinTable(
     *   name="user_purchases",
     *   joinColumns={@JoinColumn(name="product_id", referencedColumnName="id")},
     *   inverseJoinColumns={@JoinColumn(name="user_id", referencedColumnName="id")}
     * )
     */
    private $buyers;

    /**
     * Default constructor
     */
    public function __construct()
    {
        $this->buyers = new ArrayCollection();
    }

    /**
     * @return integer
     */
    public function getId()
    {
        return $this->id;
    }

    /**
     * @param string $title
     */
    public function setTitle($title)
    {
        $this->title = $title;
    }

    /**
     * Get title
     *
     * @return string
     */
    public function getTitle()
    {
        return $this->title;
    }

    /**
     * @param string $buyers
     */
    public function setBuyers($buyers)
    {
        $this->buyers = $buyers;
    }

    /**
     * @return string
     */
    public function getBuyers()
    {
        return $this->buyers;
    }

    /**
     * @param DDC1925User $buyer
     */
    public function addBuyer(DDC1925User $buyer)
    {
        $this->buyers[] = $buyer;
    }
}