<?php

namespace Doctrine\Tests\Models\DDC869;

use Doctrine\ORM\Mapping as ORM;

/**
 * @Entity
 */
#[ORM\Entity]
class DDC869ChequePayment extends DDC869Payment
{
    /** @Column(type="string") */
    #[ORM\Column(type: "string")]
    protected $serialNumber;

    public static function loadMetadata(\Doctrine\ORM\Mapping\ClassMetadataInfo $metadata)
    {
        $metadata->mapField(
            [
           'fieldName'  => 'serialNumber',
           'type'       => 'string',
            ]
        );
    }

}
