<?php

namespace Doctrine\Tests\Models\DDC1819\DataTransferObject;

/**
 * This data transfer object represents all the data used in the related view.
 */
class CustomerAddressView
{
    private $customerId;
    private $customerName;
    private $addressId;
    private $addressStreet;
    private $addressNumber;
    private $addressCity;
    private $addressCode;

    public function getCustomerId()
    {
        return $this->customerId;
    }
    
    public function getCustomerName()
    {
        return $this->customerName;
    }
    
    public function getAddressId()
    {
        return $this->addressId;
    }
    
    public function getAddressStreet()
    {
        return $this->addressStreet;
    }
    
    public function getAddressNumber()
    {
        return $this->addressNumber;
    }
    
    public function getAddressCity()
    {
        return $this->addressCity;
    }
    
    public function getAddressCode()
    {
        return $this->addressCode;
    }
}
