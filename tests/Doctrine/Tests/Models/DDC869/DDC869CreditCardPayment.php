<?php

namespace Doctrine\Tests\Models\DDC869;

use Doctrine\DBAL\Types\Type;
use Doctrine\ORM\Annotation as ORM;
use Doctrine\ORM\Mapping;

/**
 * @ORM\Entity
 */
class DDC869CreditCardPayment extends DDC869Payment
{
    /** @ORM\Column(type="string") */
    protected $creditCardNumber;

    public static function loadMetadata(Mapping\ClassMetadata $metadata)
    {
        $fieldMetadata = new Mapping\FieldMetadata('creditCardNumber');

        $fieldMetadata->setType(Type::getType('string'));

        $metadata->addProperty($fieldMetadata);
    }

}
