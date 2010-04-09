<?php
/* 
 * To change this template, choose Tools | Templates
 * and open the template in the editor.
 */

namespace Doctrine\Tests\Models\CMS;

/**
 * Description of CmsGroup
 *
 * @author robo
 * @Entity
 * @Table(name="cms_groups")
 */
class CmsGroup
{
    /**
     * @Id
     * @Column(type="integer")
     * @GeneratedValue
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

