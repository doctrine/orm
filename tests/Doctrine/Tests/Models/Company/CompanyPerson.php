<?php

namespace Doctrine\Tests\Models\Company;

/**
 * Description of CompanyPerson
 *
 * @author robo
 * @Entity
 * @Table(name="company_persons")
 * @InheritanceType("JOINED")
 * @DiscriminatorColumn(name="discr", type="string")
 * @DiscriminatorMap({
 *      "person" = "CompanyPerson",
 *      "manager" = "CompanyManager",
 *      "employee" = "CompanyEmployee"})
 */
class CompanyPerson
{
    /**
     * @Id
     * @Column(type="integer")
     * @GeneratedValue
     */
    private $id;
    /**
     * @Column
     */
    private $name;
    /**
     * @OneToOne(targetEntity="CompanyPerson", mappedBy="spouse")
     * @JoinColumn(name="spouse_id", referencedColumnName="id")
     */
    private $spouse;
    
    /**
     * @ManyToMany(targetEntity="CompanyPerson")
     * @JoinTable(name="company_persons_friends",
            joinColumns={@JoinColumn(name="person_id", referencedColumnName="id")},
            inverseJoinColumns={@JoinColumn(name="friend_id", referencedColumnName="id")})
     */
    private $friends;
    
    public function __construct() {
        $this->friends = new \Doctrine\Common\Collections\ArrayCollection;
    }

    public function getId() {
        return  $this->id;
    }

    public function getName() {
        return $this->name;
    }

    public function setName($name) {
        $this->name = $name;
    }

    public function getSpouse() {
        return $this->spouse;
    }
    
    public function getFriends() {
        return $this->friends;
    }
    
    public function addFriend(CompanyPerson $friend) {
        if ( ! $this->friends->contains($friend)) {
            $this->friends->add($friend);
            $friend->addFriend($this);
        }
    }

    public function setSpouse(CompanyPerson $spouse) {
        if ($spouse !== $this->spouse) {
            $this->spouse = $spouse;
            $this->spouse->setSpouse($this);
        }
    }
}

