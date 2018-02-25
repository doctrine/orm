<?php

declare(strict_types=1);

namespace Doctrine\Tests\Models\Company;

use Doctrine\ORM\Annotation as ORM;

/**
 * Description of CompanyPerson
 *
 * @author robo
 *
 * @ORM\Entity
 * @ORM\Table(name="company_persons")
 * @ORM\InheritanceType("JOINED")
 * @ORM\DiscriminatorColumn(name="discr", type="string")
 * @ORM\DiscriminatorMap({
 *      "person"    = CompanyPerson::class,
 *      "manager"   = CompanyManager::class,
 *      "employee"  = CompanyEmployee::class
 * })
 */
class CompanyPerson
{
    /**
     * @ORM\Id
     * @ORM\Column(type="integer")
     * @ORM\GeneratedValue
     */
    private $id;

    /**
     * @ORM\Column
     */
    private $name;

    /**
     * @ORM\OneToOne(targetEntity=CompanyPerson::class)
     * @ORM\JoinColumn(name="spouse_id", referencedColumnName="id", onDelete="CASCADE")
     */
    private $spouse;

    /**
     * @ORM\ManyToMany(targetEntity=CompanyPerson::class)
     * @ORM\JoinTable(
     *     name="company_persons_friends",
     *     joinColumns={
     *         @ORM\JoinColumn(name="person_id", referencedColumnName="id", onDelete="CASCADE")
     *     },
     *     inverseJoinColumns={
     *         @ORM\JoinColumn(name="friend_id", referencedColumnName="id", onDelete="CASCADE")
     *     }
     * )
     */
    private $friends;

    public function __construct()
    {
        $this->friends = new \Doctrine\Common\Collections\ArrayCollection;
    }

    public function getId()
    {
        return  $this->id;
    }

    public function getName()
    {
        return $this->name;
    }

    public function setName($name)
    {
        $this->name = $name;
    }

    public function getSpouse()
    {
        return $this->spouse;
    }

    public function getFriends()
    {
        return $this->friends;
    }

    public function addFriend(CompanyPerson $friend)
    {
        if ( ! $this->friends->contains($friend)) {
            $this->friends->add($friend);
            $friend->addFriend($this);
        }
    }

    public function setSpouse(CompanyPerson $spouse)
    {
        if ($spouse !== $this->spouse) {
            $this->spouse = $spouse;
            $this->spouse->setSpouse($this);
        }
    }
}
