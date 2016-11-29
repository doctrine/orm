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

    /** @Id @Column(type="integer") @GeneratedValue */
    public $id;
    /**
     * @ManyToOne(targetEntity="Author")
     */
    public $author;
    /**
     * @ManyToOne(targetEntity="Category")
     */
    public $category;
    /** @Column(type="string") */
    public $title;
}

/**
 * @Entity
 */
class MyAuthor
{

    /** @Id @Column(type="integer") @GeneratedValue */
    public $id;

}

/**
* @Entity
*/
class MyCategory
{

    /** @Id @Column(type="integer") @GeneratedValue */
    public $id;

}


/**
 * @Entity
 */
class BlogPost
{

    /** @Id @Column(type="integer") @GeneratedValue */
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

    /** @Id @Column(type="integer") @GeneratedValue */
    public $id;
    /** @Column(type="string") */
    public $name;

}

/**
 * @Entity
 */
class Person
{

    /** @Id @Column(type="integer") @GeneratedValue */
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

    /** @Id @Column(type="integer") @GeneratedValue */
    public $id;

}


/** @Entity @Table(name="groups") */
class Group
{

    /** @Id @Column(type="integer") @GeneratedValue */
    public $id;
    /** @ManyToMany(targetEntity="User", mappedBy="groups") */
    public $users;
}

/** @Entity */
class User
{

    /** @Id @Column(type="integer") @GeneratedValue */
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
    /**
     * @OneToOne(targetEntity="Avatar", mappedBy="user")
     */
    public $avatar;
}

/** @Entity */
class Avatar
{
    /** @Id @Column(type="integer") @GeneratedValue */
    public $id;
    /**
     * @OneToOne(targetEntity="User", inversedBy="avatar")
     * @JoinColumn(name="user_id", referencedColumnName="id")
     */
    public $user;
    /** @Column(type="string", length=255) */
    public $image;
    /** @Column(type="integer") */
    public $image_height;
    /** @Column(type="integer") */
    public $image_width;
    /** @Column(type="string", length=255) */
    public $image_alt_desc;
}

/** @MappedSuperclass */
abstract class Identified
{
    /** @Id @Column(type="integer") @GeneratedValue */
    private $id;

    public function getId()
    {
        return $this->id;
    }
}

/** @Entity */
class Banner extends Identified
{
    /** @Column(type="string") */
    public $name;
}
