<?php

namespace Doctrine\Tests\Models\DDC753;

use Doctrine\ORM\EntityRepository;

class DDC753CustomRepository extends EntityRepository
{

    /**
     * @return bool
     */
    public function isCustomRepository()
    {
        return true;
    }

}
