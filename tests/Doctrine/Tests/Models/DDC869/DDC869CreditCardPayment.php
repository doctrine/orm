<?php

namespace Doctrine\Tests\Models\DDC869;

use Doctrine\DBAL\Types\Type;

/**
 * @Entity
 */
class DDC869CreditCardPayment extends DDC869Payment
{
    /** @Column(type="string") */
    protected $creditCardNumber;

    public static function loadMetadata(\Doctrine\ORM\Mapping\ClassMetadata $metadata)
    {
        $metadata->addProperty('creditCardNumber', Type::getType('string'));
    }

}
