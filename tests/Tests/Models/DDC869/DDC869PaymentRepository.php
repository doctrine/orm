<?php

declare(strict_types=1);

namespace Doctrine\Tests\Models\DDC869;

use Doctrine\ORM\EntityRepository;

class DDC869PaymentRepository extends EntityRepository
{
    /**
     * Very complex method
     */
    public function isTrue(): bool
    {
        return true;
    }
}
