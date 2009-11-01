<?php

namespace Doctrine\Tests\Models\Generic;

/**
 * @Entity
 * @Table(name="decimal_model")
 */
class DecimalModel
{
    /**
     * @Id @Column(type="integer")
     * @GeneratedValue(strategy="AUTO")
     */
    public $id;
    /**
     * @Column(name="`decimal`", type="decimal", scale=5, precision=2)
     */
    public $decimal;
}