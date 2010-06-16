<?php

namespace Doctrine\Tests\Models\Generic;

/**
 * @Entity
 * @Table(name="date_time_model")
 */
class DateTimeModel
{
    /**
     * @Id @Column(type="integer")
     * @GeneratedValue
     */
    public $id;
    /**
     * @Column(name="col_datetime", type="datetime", nullable=true)
     */
    public $datetime;
    /**
     * @Column(name="col_date", type="date", nullable=true)
     */
    public $date;
    /**
     * @Column(name="col_time", type="time", nullable=true)
     */
    public $time;
}