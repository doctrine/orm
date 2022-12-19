<?php

declare(strict_types=1);

namespace Doctrine\Tests\DbalTypes;

use Doctrine\DBAL\Types\IntegerType;

class CustomIntType extends IntegerType
{
    public const NAME = 'custom_int_type';

    /**
     * {@inheritdoc}
     */
    public function getName()
    {
        return self::NAME;
    }
}
