<?php

namespace Doctrine\Tests\Models\DDC6303;

/**
 * @Entity
 * @Table(name="ddc6303_contract")
 * @InheritanceType("JOINED")
 * @DiscriminatorColumn(name="discr", type="string")
 * @DiscriminatorMap({
 *      "contract"    = "DDC6303Contract",
 *      "contract_b"  = "DDC6303ContractB",
 *      "contract_a"  = "DDC6303ContractA"
 * })
 */
class DDC6303Contract
{
    /**
     * @Id
     * @Column(type="integer")
     * @GeneratedValue
     */
    public $id;
}
