<?php

namespace Doctrine\Tests\Models\Company;

/**
 * @Entity
 * @Table(name="company_managers")
 */
class CompanyManager extends CompanyEmployee
{
    /**
     * @Column(type="string", length=250)
     */
    private $title;

    /**
     * @OneToOne(targetEntity="CompanyCar", cascade={"persist"})
     * @JoinColumn(name="car_id", referencedColumnName="id")
     */
    private $car;

    /**
     * @ManyToMany(targetEntity="CompanyFlexContract", mappedBy="managers", fetch="EXTRA_LAZY")
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
