<?php

namespace Doctrine\Tests\Models\DDC869;

use Doctrine\DBAL\Types\Type;

/**
 * @Entity
 */
class DDC869ChequePayment extends DDC869Payment
{

    /** @Column(type="string") */
    protected $serialNumber;

    public static function loadMetadata(\Doctrine\ORM\Mapping\ClassMetadata $metadata)
    {
        $metadata->addProperty('serialNumber', Type::getType('string'));
    }

}
