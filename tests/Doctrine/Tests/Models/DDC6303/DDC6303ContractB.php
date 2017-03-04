<?php

namespace Doctrine\Tests\Models\DDC6303;

/**
 * @Entity
 * @Table(name="ddc6303_contracts_b")
 */
class DDC6303ContractB extends DDC6303Contract
{
    /**
     * @Column(type="simple_array", nullable=true)
     *
     * @var array
     */
    public $originalData;
}