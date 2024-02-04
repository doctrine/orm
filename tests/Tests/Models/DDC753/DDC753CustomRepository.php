<?php

declare(strict_types=1);

namespace Doctrine\Tests\Models\DDC753;

use Doctrine\ORM\EntityRepository;

class DDC753CustomRepository extends EntityRepository
{
    public function isCustomRepository(): bool
    {
        return true;
    }
}
