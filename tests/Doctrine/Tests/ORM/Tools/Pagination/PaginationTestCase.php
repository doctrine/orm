<?php

namespace Doctrine\Tests\ORM\Tools\Pagination;

use Doctrine\Tests\OrmTestCase;

abstract class PaginationTestCase extends OrmTestCase
{
    /**
     * @var \Doctrine\ORM\EntityManagerInterface
     */
    public $entityManager;

    public function setUp()
    {
        $this->entityManager = $this->_getTestEntityManager();
    }
}


/**
* @Entity
*/
class MyBlogPost
{

    /** @Id @column(type="integer") @generatedValue */
    public $id;
    /**
     * @ManyToOne(targetEntity="Author")
     */
    public $author;
    /**
     * @ManyToOne(targetEntity="Category")
     */
    public $category;
    /** @column(type="string") */
    public $title;
}

/**
 * @Entity
 */
class MyAuthor
{

    /** @Id @column(type="integer") @generatedValue */
    public $id;

}

/**
* @Entity
*/
class MyCategory
{

    /** @id @column(type="integer") @generatedValue */
    public $id;

}


/**
 * @Entity
 */
class BlogPost
{

    /** @Id @column(type="integer") @generatedValue */
    public $id;
    /**
     * @ManyToOne(targetEntity="Author")
     */
    public $author;
    /**
     * @ManyToOne(targetEntity="Category")
     */
    public $category;
}

/**
 * @Entity
 */
class Author
{

    /** @Id @column(type="integer") @generatedValue */
    public $id;
    /** @Column(type="string") */
    public $name;

}

/**
 * @Entity
 */
class Person
{

    /** @Id @column(type="integer") @generatedValue */
    public $id;
    /** @Column(type="string") */
    public $name;
    /** @Column(type="string") */
    public $biography;

}

/**
 * @Entity
 */
class Category
{

    /** @id @column(type="integer") @generatedValue */
    public $id;

}


/** @Entity @Table(name="groups") */
class Group
{

    /** @Id @column(type="integer") @generatedValue */
    public $id;
    /** @ManyToMany(targetEntity="User", mappedBy="groups") */
    public $users;
}

/** @Entity */
class User
{

    /** @Id @column(type="integer") @generatedValue */
    public $id;
    /**
     * @ManyToMany(targetEntity="Group", inversedBy="users")
     * @JoinTable(
     * name="user_group",
     * joinColumns = {@JoinColumn(name="user_id", referencedColumnName="id")},
     * inverseJoinColumns = {@JoinColumn(name="group_id", referencedColumnName="id")}
     * )
     */
    public $groups;
}
