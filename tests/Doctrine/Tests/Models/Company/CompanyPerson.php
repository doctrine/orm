<?php

namespace Doctrine\Tests\Models\Company;

/**
 * Description of CompanyPerson
 *
 * @author robo
 * @Entity
 * @Table(name="company_persons")
 * @DiscriminatorValue("person")
 * @InheritanceType("JOINED")
 * @DiscriminatorColumn(name="discr", type="string")
 * @SubClasses({"Doctrine\Tests\Models\Company\CompanyEmployee",
        "Doctrine\Tests\Models\Company\CompanyManager"})
 */
class CompanyPerson
{
    /**
     * @Id
     * @Column(type="integer")
     * @GeneratedValue(strategy="AUTO")
     */
    private $id;
    /**
     * @Column(type="string")
     */
    private $name;
    /**
     * @OneToOne(targetEntity="CompanyPerson")
     * @JoinColumn(name="spouse_id", referencedColumnName="id")
     */
    private $spouse;
    
    /**
     * @ManyToMany(targetEntity="CompanyPerson")
     * @JoinTable(name="company_persons_friends",
            joinColumns={{"name"="person_id", "referencedColumnName"="id"}},
            inverseJoinColumns={{"name"="friend_id", "referencedColumnName"="id"}})
     */
    private $friends;

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
        if ( ! $this->friends) {
            $this->friends = new \Doctrine\Common\Collections\Collection;
        }
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

