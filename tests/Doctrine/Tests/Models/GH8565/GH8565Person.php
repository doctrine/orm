<?php

declare(strict_types=1);

namespace Doctrine\Tests\Models\GH8565;

/**
 * @Entity
 * @Table(name="gh8565_persons")
 * @InheritanceType("JOINED")
 * @DiscriminatorColumn(name="discr", type="string")
 * @DiscriminatorMap({
 *      "person"    = "GH8565Person",
 *      "manager"   = "GH8565Manager",
 *      "employee"  = "GH8565Employee"
 * })
 */
class GH8565Person
{
    /**
     * @var int
     * @Id
     * @Column(type="integer")
     * @GeneratedValue
     */
    public $id;
}
