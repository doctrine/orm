<?php

namespace Doctrine\Tests\Models\DDC3899;

/**
 * @Entity
 */
class DDC3899FixContract extends DDC3899Contract
{
    /** @column(type="integer") */
    public $fixPrice = 0;
}
