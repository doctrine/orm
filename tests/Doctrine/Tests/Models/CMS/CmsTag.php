<?php

namespace Doctrine\Tests\Models\CMS;

/**
 * Description of CmsTag
 *
 * @Entity
 * @Table(name="cms_tags")
 */
class CmsTag
{
    /**
     * @Id
     * @Column(type="integer")
     * @GeneratedValue
     */
    public $id;
    /**
     * @Column(length=50, name="tag_name", nullable=true)
     */
    public $name;
    /**
     * @ManyToMany(targetEntity="CmsUser", mappedBy="tags")
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

