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
     * @Column(type="datetime")
     */
    public $datetime;
    /**
     * @Column(type="date")
     */
    public $date;
    /**
     * @Column(type="time")
     */
    public $time;
}