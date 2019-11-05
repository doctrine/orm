<?php

namespace Doctrine\Tests\Models\CMSCustomId;

/**
 * Description of CmsGroup
 *
 * @author robo
 * @Entity
 * @Table(name="cms_groups_customid")
 */
class CmsGroup
{
    /**
     * @Id
     * @Column(type="CustomIdObject")
     * @GeneratedValue(strategy="NONE")
     */
    public $id;
    /**
     * @Column(length=50)
     */
    public $name;
    /**
     * @ManyToMany(targetEntity="CmsUser", mappedBy="groups")
     */
    public $users;

    public function setName($name) {
        $this->name = $name;
    }

    public function getName() {
        return $this->name;
    }

    public function addUser(CmsUser $user) {
        $this->users[] = $user;
    }

    public function getUsers() {
        return $this->users;
    }
}

