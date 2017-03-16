<?php

namespace Doctrine\Tests\Models\DDC869;

use Doctrine\ORM\EntityRepository;

class DDC869PaymentRepository extends EntityRepository
{

    /**
     * Very complex method
     *
     * @return bool
     */
    public function isTrue()
    {
        return true;
    }
}
