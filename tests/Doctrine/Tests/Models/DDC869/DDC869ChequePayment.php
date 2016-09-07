<?php

namespace Doctrine\Tests\Models\DDC869;

use Doctrine\DBAL\Types\Type;
use Doctrine\ORM\Mapping;

/**
 * @Entity
 */
class DDC869ChequePayment extends DDC869Payment
{

    /** @Column(type="string") */
    protected $serialNumber;

    public static function loadMetadata(\Doctrine\ORM\Mapping\ClassMetadata $metadata)
    {
        $fieldMetadata = new Mapping\FieldMetadata('serialNumber');

        $fieldMetadata->setType(Type::getType('string'));

        $metadata->addProperty($fieldMetadata);
    }

}
