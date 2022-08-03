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
     * @var int
     * @Id
     * @Column(type="integer")
     * @GeneratedValue
     */
    private $id;

    /**
     * @var string
     * @Column
     */
    private $name;

    /**
     * @var CmsEmployee
     * @OneToOne(targetEntity="CmsEmployee")
     * @JoinColumn(name="spouse_id", referencedColumnName="id")
     */
    private $spouse;

    public function getId(): int
    {
        return $this->id;
    }

    public function getName(): string
    {
        return $this->name;
    }

    public function getSpouse(): ?CmsEmployee
    {
        return $this->spouse;
    }
}
