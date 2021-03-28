<?php

declare(strict_types=1);

namespace Doctrine\Tests\Models\Generic;

/**
 * @Entity
 * @Table(name="date_time_model")
 */
class DateTimeModel
{
    /**
     * @var int
     * @Id @Column(type="integer")
     * @GeneratedValue
     */
    public $id;

    /**
     * @var DateTime|null
     * @Column(name="col_datetime", type="datetime", nullable=true)
     */
    public $datetime;

    /**
     * @var DateTime|null
     * @Column(name="col_date", type="date", nullable=true)
     */
    public $date;

    /**
     * @var DateTime|null
     * @Column(name="col_time", type="time", nullable=true)
     */
    public $time;
}
