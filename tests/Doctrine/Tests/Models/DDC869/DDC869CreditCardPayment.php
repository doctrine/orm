<?php

namespace Doctrine\Tests\Models\DDC869;

use Doctrine\DBAL\Types\Type;
use Doctrine\ORM\Mapping;

/**
 * @Entity
 */
class DDC869CreditCardPayment extends DDC869Payment
{
    /** @Column(type="string") */
    protected $creditCardNumber;

    public static function loadMetadata(\Doctrine\ORM\Mapping\ClassMetadata $metadata)
    {
        $fieldMetadata = new Mapping\FieldMetadata('creditCardNumber');

        $fieldMetadata->setType(Type::getType('string'));

        $metadata->addProperty($fieldMetadata);
    }

}
