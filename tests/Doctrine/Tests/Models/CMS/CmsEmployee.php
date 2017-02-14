<?php

namespace Doctrine\Tests\Models\CMS;

use Doctrine\ORM\Annotation as ORM;

/**
 * Description of CmsEmployee
 *
 * @author robo
 *
 * @ORM\Entity
 * @ORM\Table(name="cms_employees")
 */
class CmsEmployee
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
     * @ORM\OneToOne(targetEntity="CmsEmployee")
     * @ORM\JoinColumn(name="spouse_id", referencedColumnName="id")
     */
    private $spouse;

    public function getId() {
        return $this->id;
    }

    public function getName() {
        return $this->name;
    }

    public function getSpouse() {
        return $this->spouse;
    }
}

