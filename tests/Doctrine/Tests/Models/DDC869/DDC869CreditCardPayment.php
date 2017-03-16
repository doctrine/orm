<?php

namespace Doctrine\Tests\Models\DDC869;

/**
 * @Entity
 */
class DDC869CreditCardPayment extends DDC869Payment
{

    /** @Column(type="string") */
    protected $creditCardNumber;

    public static function loadMetadata(\Doctrine\ORM\Mapping\ClassMetadataInfo $metadata)
    {
        $metadata->mapField(
            [
           'fieldName'  => 'creditCardNumber',
           'type'       => 'string',
            ]
        );
    }

}
