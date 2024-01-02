<?php

declare(strict_types=1);

namespace Doctrine\Tests\Models\DDC869;

use Doctrine\ORM\Mapping as ORM;
use Doctrine\ORM\Mapping\ClassMetadata;

#[ORM\Entity]
class DDC869ChequePayment extends DDC869Payment
{
    /** @var string */
    #[ORM\Column(type: 'string')]
    protected $serialNumber;

    public static function loadMetadata(ClassMetadata $metadata): void
    {
        $metadata->mapField(
            [
                'fieldName'  => 'serialNumber',
                'type'       => 'string',
            ],
        );
    }
}
