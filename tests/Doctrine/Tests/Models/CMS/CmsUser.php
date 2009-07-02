<?php

namespace Doctrine\Tests\Models\CMS;

/**
 * @Entity
 * @Table(name="cms_users")
 */
class CmsUser
{
    /**
     * @Id @Column(type="integer")
     * @GeneratedValue(strategy="AUTO")
     */
    public $id;
    /**
     * @Column(type="string", length=50)
     */
    public $status;
    /**
     * @Column(type="string", length=255)
     */
    public $username;
    /**
     * @Column(type="string", length=255)
     */
    public $name;
    /**
     * @OneToMany(targetEntity="CmsPhonenumber", mappedBy="user", cascade={"save", "delete"})
     */
    public $phonenumbers;
    /**
     * @OneToMany(targetEntity="CmsArticle", mappedBy="user")
     */
    public $articles;
    /**
     * @OneToOne(targetEntity="CmsAddress", mappedBy="user", cascade={"save"})
     */
    public $address;
    /**
     * @ManyToMany(targetEntity="CmsGroup", cascade={"save"})
     * @JoinTable(name="cms_users_groups",
            joinColumns={{"name"="user_id", "referencedColumnName"="id"}},
            inverseJoinColumns={{"name"="group_id", "referencedColumnName"="id"}})
     */
    public $groups;

    public function getId() {
        return $this->id;
    }

    public function getStatus() {
        return $this->status;
    }

    public function getUsername() {
        return $this->username;
    }

    public function getName() {
        return $this->name;
    }

    /**
     * Adds a phonenumber to the user.
     *
     * @param CmsPhonenumber $phone
     */
    public function addPhonenumber(CmsPhonenumber $phone) {
        $this->phonenumbers[] = $phone;
        $phone->setUser($this);
    }

    public function getPhonenumbers() {
        return $this->phonenumbers;
    }

    public function addArticle(CmsArticle $article) {
        $this->articles[] = $article;
        $article->setAuthor($this);
    }

    public function addGroup(CmsGroup $group) {
        $this->groups[] = $group;
        $group->addUser($this);
    }

    public function getGroups() {
        return $this->groups;
    }

    public function removePhonenumber($index) {
        if (isset($this->phonenumbers[$index])) {
            $ph = $this->phonenumbers[$index];
            unset($this->phonenumbers[$index]);
            $ph->user = null;
            return true;
        }
        return false;
    }
}
