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
    /**
     * @Column(type="integer")
     * @Id
     */
    private $id;

    /**
     * @ManyToMany(targetEntity="Doctrine\Tests\Models\NonPublicSchemaJoins\User", inversedBy="authors")
     * @JoinTable(name="author_reader", schema="readers",
     *      joinColumns={@JoinColumn(name="author_id", referencedColumnName="id")},
     *      inverseJoinColumns={@JoinColumn(name="reader_id", referencedColumnName="id")}
     * )
     *
     * @var User[]
     */
    private $readers;

    /**
     * @ManyToMany(targetEntity="Doctrine\Tests\Models\NonPublicSchemaJoins\User", mappedBy="readers")
     *
     * @var User[]
     */
    private $authors;

    public function setId($id)
    {
        $this->id = $id;
    }

    /**
     * @return User[]
     */
    public function getReaders()
    {
        return $this->readers;
    }

    /**
     * @param User[] $readers
     * @return User
     */
    public function setReaders($readers)
    {
        $this->readers = $readers;

        return $this;
    }

    /**
     * @return User[]
     */
    public function getAuthors()
    {
        return $this->authors;
    }

    /**
     * @param User[] $authors
     * @return User
     */
    public function setAuthors($authors)
    {
        $this->authors = $authors;

        return $this;
    }
}
