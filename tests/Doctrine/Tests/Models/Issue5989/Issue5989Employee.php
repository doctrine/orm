<?php

namespace Doctrine\Tests\Models\Issue5989;

/**
 * @Entity
 * @Table(name="issue5989_employees")
 */
class Issue5989Employee extends Issue5989Person
{
    /**
     * @Column(type="simple_array", nullable=true)
     *
     * @var array
     */
    public $tags;
}
