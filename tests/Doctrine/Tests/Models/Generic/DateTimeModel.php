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
     * @GeneratedValue(strategy="AUTO")
     */
    public $id;
    /**
     * @Column(name="col_datetime", type="datetime")
     */
    public $datetime;
    /**
     * @Column(name="col_date", type="date")
     */
    public $date;
    /**
     * @Column(name="col_time", type="time")
     */
    public $time;
}