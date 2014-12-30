<?php
/**
 * Created by PhpStorm.
 * User: marco
 * Date: 30.12.14
 * Time: 20:48
 */

namespace Doctrine\Tests\Models\DDC3467;


/**
 * @Entity @Table @InheritanceType("SINGLE_TABLE")
 * @DiscriminatorMap({"sun" = "DDC3467Sun"})
 */
abstract class DDC3467Planet {
    /** @Id @Column(type="integer") */
    private $id;
    /** @Embedded(class="DDC3467Position") */
    private $position;
    /** @Column(type="string") */
    private $name;
}