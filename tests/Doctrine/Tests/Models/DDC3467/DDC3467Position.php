<?php
/**
 * Created by PhpStorm.
 * User: marco
 * Date: 30.12.14
 * Time: 20:48
 */

namespace Doctrine\Tests\Models\DDC3467;


/** @Embeddable */
class DDC3467Position {
    /** @Column(type="float") */
    private $x;
    /** @Column(type="float") */
    private $y;
}