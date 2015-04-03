<?php

namespace Doctrine\Tests\Models\NonPublicSchemaJoins;

/**
 * Doctrine\Tests\Models\NonPublicSchemaJoins\User
 *
 * @Entity
 * @Table(name="readers.user")
 */
class User
{
    const CLASSNAME = __CLASS__;

    /**
     * @Column(type="integer")
     * @Id
     */
    public $id;

    /**
     * @ManyToMany(targetEntity="Doctrine\Tests\Models\NonPublicSchemaJoins\User", inversedBy="authors")
     * @JoinTable(
     *      name="author_reader",
     *      schema="readers",
     *      joinColumns={@JoinColumn(name="author_id", referencedColumnName="id")},
     *      inverseJoinColumns={@JoinColumn(name="reader_id", referencedColumnName="id")}
     * )
     *
     * @var User[]
     */
    public $readers;

    /**
     * @ManyToMany(targetEntity="Doctrine\Tests\Models\NonPublicSchemaJoins\User", mappedBy="readers")
     *
     * @var User[]
     */
    public $authors;
}
