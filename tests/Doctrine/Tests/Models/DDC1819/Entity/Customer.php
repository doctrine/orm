<?php

namespace Doctrine\Tests\Models\DDC1819\Entity;

/**
 * This entity represents a customer. 
 *
 * @Entity
 * @Table(name="ddc1819_customer")
 */
class Customer
{
    /**
     * @Column(type="integer")
     * @Id @GeneratedValue
     */
    private $id;

    /**
     * @Column(length=100)
     */
    private $name;

    /**
     * @OneToOne(targetEntity="Address")
     * @JoinColumn(name="address_id", referencedColumnName="id")
     */
    private $address;

    public function getId()
    {
        return $this->id;
    }
    
    public function getName()
    {
        return $this->name;
    }
    
    public function getAddress()
    {
        return $this->address;
    }
}
