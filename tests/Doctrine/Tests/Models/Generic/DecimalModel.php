<?php

declare(strict_types=1);

namespace Doctrine\Tests\Models\Generic;

/**
 * @Entity
 * @Table(name="decimal_model")
 */
class DecimalModel
{
    /**
     * @var int
     * @Id @Column(type="integer")
     * @GeneratedValue(strategy="AUTO")
     */
    public $id;

    /**
     * @var float
     * @Column(name="`decimal`", type="decimal", scale=2, precision=5)
     */
    public $decimal;

    /**
     * @var float
     * @Column(name="`high_scale`", type="decimal", scale=4, precision=14)
     */
    public $highScale;
}
