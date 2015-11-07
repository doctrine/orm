<?php

namespace Doctrine\Tests\Models\DDC3899;

/**
 * @Entity
 */
class DDC3899FlexContract extends DDC3899Contract
{
    /** @column(type="integer") */
    public $hoursWorked = 0;

    /** @column(type="integer") */
    public $pricePerHour = 0;
}
