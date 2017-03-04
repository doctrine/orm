<?php

namespace Doctrine\Tests\Models\DDC6303;

/**
 * @Entity
 * @Table(name="ddc6303_contracts_a")
 */
class DDC6303ContractA extends DDC6303Contract
{
    /**
     * @Column(type="string", nullable=true)
     *
     * @var string
     */
    public $originalData;
}