<?php

declare(strict_types=1);

namespace Doctrine\Tests\Models\DDC753;

use Doctrine\ORM\EntityRepository;

class DDC753DefaultRepository extends EntityRepository
{
    /**
     * @return bool
     */
    public function isDefaultRepository()
    {
        return true;
    }

}
