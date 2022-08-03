<?php

declare(strict_types=1);

namespace Doctrine\Tests\Models\DDC3899;

/**
 * @Entity
 */
class DDC3899FixContract extends DDC3899Contract
{
    /**
     * @var int
     * @column(type="integer")
     */
    public $fixPrice = 0;
}
