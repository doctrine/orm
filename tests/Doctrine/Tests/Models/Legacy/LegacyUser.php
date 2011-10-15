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
    public $_id;
    /**
     * @Column(name="sUsername", type="string", length=255, unique=true)
     */
    public $_username;
    /**
     * @Column(type="string", length=255)
     */
    public $_name;
    /**
     * @OneToMany(targetEntity="LegacyArticle", mappedBy="_user")
     */
    public $_articles;
    /**
     * @OneToMany(targetEntity="LegacyUserReference", mappedBy="_source", cascade={"remove"})
     */
    public $_references;
    /**
     * @ManyToMany(targetEntity="LegacyCar", inversedBy="_users", cascade={"persist", "merge"})
     * @JoinTable(name="legacy_users_cars",
     *      joinColumns={@JoinColumn(name="iUserId", referencedColumnName="iUserId")},
     *      inverseJoinColumns={@JoinColumn(name="iCarId", referencedColumnName="iCarId")}
     *      )
     */
    public $_cars;
    public function __construct() {
        $this->_articles = new ArrayCollection;
        $this->_references = new ArrayCollection;
        $this->_cars = new ArrayCollection;
    }

    public function getId() {
        return $this->_id;
    }

    public function getUsername() {
        return $this->_username;
    }

    public function addArticle(LegacyArticle $article) {
        $this->_articles[] = $article;
        $article->setAuthor($this);
    }

    public function addReference($reference)
    {
        $this->_references[] = $reference;
    }

    public function references()
    {
        return $this->_references;
    }

    public function addCar(LegacyCar $car) {
        $this->_cars[] = $car;
        $car->addUser($this);
    }

    public function getCars() {
        return $this->_cars;
    }
}
