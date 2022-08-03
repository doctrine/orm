<?php

declare(strict_types=1);

namespace Doctrine\Tests\Models\Issue5989;

/**
 * @Entity
 * @Table(name="issue5989_persons")
 * @InheritanceType("JOINED")
 * @DiscriminatorColumn(name="discr", type="string")
 * @DiscriminatorMap({
 *      "person"    = "Issue5989Person",
 *      "manager"   = "Issue5989Manager",
 *      "employee"  = "Issue5989Employee"
 * })
 */
class Issue5989Person
{
    /**
     * @var int
     * @Id
     * @Column(type="integer")
     * @GeneratedValue
     */
    public $id;
}
