<?php

namespace Doctrine\Tests\Models\NonPublicSchemaJoins;

use Doctrine\ORM\Annotation as ORM;

/**
 * Doctrine\Tests\Models\NonPublicSchemaJoins\User
 *
 * @ORM\Entity
 * @ORM\Table(name="readers.user")
 */
class User
{
    /**
     * @ORM\Column(type="integer")
     * @ORM\Id
     */
    public $id;

    /**
     * @ORM\ManyToMany(targetEntity="Doctrine\Tests\Models\NonPublicSchemaJoins\User", inversedBy="authors")
     * @ORM\JoinTable(
     *      name="author_reader",
     *      schema="readers",
     *      joinColumns={@ORM\JoinColumn(name="author_id", referencedColumnName="id")},
     *      inverseJoinColumns={@ORM\JoinColumn(name="reader_id", referencedColumnName="id")}
     * )
     *
     * @var User[]
     */
    public $readers;

    /**
     * @ORM\ManyToMany(targetEntity="Doctrine\Tests\Models\NonPublicSchemaJoins\User", mappedBy="readers")
     *
     * @var User[]
     */
    public $authors;
}
