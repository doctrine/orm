<?php

declare(strict_types=1);

namespace Doctrine\Tests\Models\CMS;

/**
 * Description of CmsEmployee
 *
 * @Entity
 * @Table(name="cms_employees")
 */
class CmsEmployee
{
    /**
     * @Id
     * @Column(type="integer")
     * @GeneratedValue
     */
    private $id;

    /** @Column */
    private $name;

    /**
     * @OneToOne(targetEntity="CmsEmployee")
     * @JoinColumn(name="spouse_id", referencedColumnName="id")
     */
    private $spouse;

    public function getId()
    {
        return $this->id;
    }

    public function getName()
    {
        return $this->name;
    }

    public function getSpouse()
    {
        return $this->spouse;
    }
}
