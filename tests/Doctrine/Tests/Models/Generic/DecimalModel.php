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
     * @Column(name="`decimal`", type="decimal", scale=2, precision=5)
     */
    public $decimal;

    /**
     * @Column(name="`high_scale`", type="decimal", scale=4, precision=14)
     */
    public $highScale;
}