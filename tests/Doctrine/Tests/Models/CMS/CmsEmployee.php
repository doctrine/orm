<?php

namespace Doctrine\Tests\Models\CMS;

/**
 * Description of CmsEmployee
 *
 * @author robo
 * @DoctrineEntity
 * @DoctrineTable(name="cms_employees")
 */
class CmsEmployee
{
    /**
     * @DoctrineId
     * @DoctrineColumn(type="integer")
     * @DoctrineGeneratedValue(strategy="auto")
     */
    private $id;

    /**
     * @DoctrineColumn(type="string")
     */
    private $name;

    /**
     * @DoctrineOneToOne(targetEntity="CmsEmployee")
     * @DoctrineJoinColumn(name="spouse_id", referencedColumnName="id")
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

