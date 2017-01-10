<?php

namespace Doctrine\Tests\Models\Legacy;

use Doctrine\Common\Collections\ArrayCollection;

/**
 * @Entity
 * @Table(name="legacy_users")
 */
class LegacyUser
{
    /**
     * @Id
     * @GeneratedValue
     * @Column(name="iUserId", type="integer", nullable=false)
     */
    public $id;
    /**
     * @Column(name="sUsername", type="string", length=255, unique=true)
     */
    public $username;
    /**
     * @Column(type="string", length=255, name="name")
     */
    public $name;
    /**
     * @OneToMany(targetEntity="LegacyArticle", mappedBy="user")
     */
    public $articles;
    /**
     * @OneToMany(targetEntity="LegacyUserReference", mappedBy="source", cascade={"remove"})
     */
    public $references;
    /**
     * @ManyToMany(targetEntity="LegacyCar", inversedBy="users", cascade={"persist", "merge"})
     * @JoinTable(name="legacy_users_cars",
     *      joinColumns={@JoinColumn(name="iUserId", referencedColumnName="iUserId")},
     *      inverseJoinColumns={@JoinColumn(name="iCarId", referencedColumnName="iCarId")}
     *      )
     */
    public $cars;
    
    public function __construct()
    {
        $this->articles = new ArrayCollection;
        $this->references = new ArrayCollection;
        $this->cars = new ArrayCollection;
    }

    public function getId() {
        return $this->id;
    }

    public function getUsername() {
        return $this->username;
    }

    public function addArticle(LegacyArticle $article) {
        $this->articles[] = $article;
        $article->setAuthor($this);
    }

    public function addReference($reference)
    {
        $this->references[] = $reference;
    }

    public function references()
    {
        return $this->references;
    }

    public function addCar(LegacyCar $car) {
        $this->cars[] = $car;
        $car->addUser($this);
    }

    public function getCars() {
        return $this->cars;
    }
}
