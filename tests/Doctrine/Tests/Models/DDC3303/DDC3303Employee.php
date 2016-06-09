<?php
namespace Doctrine\Tests\Models\DDC3303;

/**
 * @Entity
 * @Table(name="ddc3303_employee")
 */
class DDC3303Employee extends DDC3303Person
{
    /**
     * @Column(type="string")
     *
     * @var string
     */
    private $company;

    public function __construct($name, DDC3303Address $address, $company)
    {
        parent::__construct($name, $address);

        $this->company = $company;
    }

    /**
     * @return string
     */
    public function getCompany()
    {
        return $this->company;
    }

    /**
     * @param string $company
     */
    public function setCompany($company)
    {
        $this->company = $company;
    }
}
