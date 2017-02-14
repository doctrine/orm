<?php

namespace Doctrine\Tests\Models\CMS;

use Doctrine\ORM\Annotation as ORM;

/**
 * Description of CmsTag
 *
 * @ORM\Entity
 * @ORM\Table(name="cms_tags")
 */
class CmsTag
{
    /**
     * @ORM\Id
     * @ORM\Column(type="integer")
     * @ORM\GeneratedValue
     */
    public $id;
    /**
     * @ORM\Column(length=50, name="tag_name", nullable=true)
     */
    public $name;
    /**
     * @ORM\ManyToMany(targetEntity="CmsUser", mappedBy="tags")
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

