<?php

namespace Doctrine\Tests\Models\CMS;

use Doctrine\ORM\Annotation as ORM;

/**
 * Description of CmsGroup
 *
 * @author robo
 *
 * @ORM\Entity
 * @ORM\Table(name="cms_groups")
 */
class CmsGroup
{
    /**
     * @ORM\Id
     * @ORM\Column(type="integer")
     * @ORM\GeneratedValue
     */
    public $id;
    /**
     * @ORM\Column(length=50)
     */
    public $name;
    /**
     * @ORM\ManyToMany(targetEntity="CmsUser", mappedBy="groups")
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

