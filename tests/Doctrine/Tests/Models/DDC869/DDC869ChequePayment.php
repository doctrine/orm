<?php

declare(strict_types=1);

namespace Doctrine\Tests\Models\DDC869;

use Doctrine\DBAL\Types\Type;
use Doctrine\ORM\Annotation as ORM;
use Doctrine\ORM\Mapping;

/**
 * @ORM\Entity
 */
class DDC869ChequePayment extends DDC869Payment
{
    /** @ORM\Column(type="string") */
    protected $serialNumber;
}
