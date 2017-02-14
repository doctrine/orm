<?php

namespace Doctrine\Tests\Models\Issue5989;

use Doctrine\ORM\Annotation as ORM;

/**
 * @ORM\Entity
 * @ORM\Table(name="issue5989_persons")
 * @ORM\InheritanceType("JOINED")
 * @ORM\DiscriminatorColumn(name="discr", type="string")
 * @ORM\DiscriminatorMap({
 *      "person"    = "Issue5989Person",
 *      "manager"   = "Issue5989Manager",
 *      "employee"  = "Issue5989Employee"
 * })
 */
class Issue5989Person
{
    /**
     * @ORM\Id
     * @ORM\Column(type="integer")
     * @ORM\GeneratedValue
     */
    public $id;
}
