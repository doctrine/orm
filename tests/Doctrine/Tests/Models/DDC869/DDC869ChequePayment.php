<?php

namespace Doctrine\Tests\Models\DDC869;

/**
 * @Entity
 */
class DDC869ChequePayment extends DDC869Payment
{

    /** @Column(type="string") */
    protected $serialNumber;

    public static function loadMetadata(\Doctrine\ORM\Mapping\ClassMetadata $metadata)
    {
        $metadata->mapField(
            [
           'fieldName'  => 'serialNumber',
           'type'       => 'string',
            ]
        );
    }

}
