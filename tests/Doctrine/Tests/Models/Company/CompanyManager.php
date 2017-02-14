<?php

namespace Doctrine\Tests\Models\Company;

use Doctrine\ORM\Annotation as ORM;

/**
 * @ORM\Entity
 * @ORM\Table(name="company_managers")
 */
class CompanyManager extends CompanyEmployee
{
    /**
     * @ORM\Column(type="string", length=250)
     */
    private $title;

    /**
     * @ORM\OneToOne(targetEntity="CompanyCar", cascade={"persist"})
     * @ORM\JoinColumn(name="car_id", referencedColumnName="id")
     */
    private $car;

    /**
     * @ORM\ManyToMany(targetEntity="CompanyFlexContract", mappedBy="managers", fetch="EXTRA_LAZY")
     */
    public $managedContracts;

    public function getTitle() {
        return $this->title;
    }

    public function setTitle($title) {
        $this->title = $title;
    }

    public function getCar() {
        return $this->car;
    }

    public function setCar(CompanyCar $car) {
        $this->car = $car;
    }
}
