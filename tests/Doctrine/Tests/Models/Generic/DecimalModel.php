<?php

namespace Doctrine\Tests\Models\Generic;

/**
 * @Entity
 * @Table(name="date_time_model")
 */
class DecimalModel
{
    /**
     * @Id @Column(type="integer")
     * @GeneratedValue(strategy="AUTO")
     */
    public $id;
    /**
     * @Column(type="decimal", scale=5, precision=2)
     */
    public $decimal;
}