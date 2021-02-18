<?php

declare(strict_types=1);

namespace Doctrine\Tests\Models\DDC3899;

/**
 * @Entity
 */
class DDC3899FlexContract extends DDC3899Contract
{
    /**
     * @var int
     * @column(type="integer")
     */
    public $hoursWorked = 0;

    /**
     * @var int
     * @column(type="integer")
     */
    public $pricePerHour = 0;
}
