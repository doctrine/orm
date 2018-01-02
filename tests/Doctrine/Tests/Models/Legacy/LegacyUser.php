<?php

declare(strict_types=1);

namespace Doctrine\Tests\Models\Legacy;

use Doctrine\Common\Collections\ArrayCollection;
use Doctrine\ORM\Annotation as ORM;

/**
 * @ORM\Entity
 * @ORM\Table(name="legacy_users")
 */
class LegacyUser
{
    /**
     * @ORM\Id
     * @ORM\GeneratedValue
     * @ORM\Column(name="iUserId", type="integer", nullable=false)
     */
    public $id;

    /**
     * @ORM\Column(name="sUsername", type="string", length=255, unique=true)
     */
    public $username;

    /**
     * @ORM\Column(type="string", length=255, name="name")
     */
    public $name;

    /**
     * @ORM\OneToMany(targetEntity=LegacyArticle::class, mappedBy="user")
     */
    public $articles;

    /**
     * @ORM\OneToMany(targetEntity=LegacyUserReference::class, mappedBy="source", cascade={"remove"})
     */
    public $references;

    /**
     * @ORM\ManyToMany(targetEntity=LegacyCar::class, inversedBy="users", cascade={"persist"})
     * @ORM\JoinTable(name="legacy_users_cars",
     *      joinColumns={@ORM\JoinColumn(name="iUserId", referencedColumnName="iUserId")},
     *      inverseJoinColumns={@ORM\JoinColumn(name="iCarId", referencedColumnName="iCarId")}
     *      )
     */
    public $cars;

    public function __construct()
    {
        $this->articles = new ArrayCollection;
        $this->references = new ArrayCollection;
        $this->cars = new ArrayCollection;
    }

    public function getId()
    {
        return $this->id;
    }

    public function getUsername()
    {
        return $this->username;
    }

    public function addArticle(LegacyArticle $article)
    {
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

    public function addCar(LegacyCar $car)
    {
        $this->cars[] = $car;
        $car->addUser($this);
    }

    public function getCars()
    {
        return $this->cars;
    }
}
