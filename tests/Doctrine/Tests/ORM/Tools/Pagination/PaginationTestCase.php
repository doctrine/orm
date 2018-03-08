<?php

declare(strict_types=1);

namespace Doctrine\Tests\ORM\Tools\Pagination;

use Doctrine\ORM\Annotation as ORM;
use Doctrine\ORM\EntityManagerInterface;
use Doctrine\Tests\OrmTestCase;

abstract class PaginationTestCase extends OrmTestCase
{
    /** @var EntityManagerInterface */
    public $entityManager;

    public function setUp()
    {
        $this->entityManager = $this->getTestEntityManager();
    }

    public function tearDown()
    {
        $this->entityManager = null;
    }
}

/**
 * @ORM\Entity
 */
class MyBlogPost
{
    /** @ORM\Id @ORM\Column(type="integer") @ORM\GeneratedValue */
    public $id;
    /** @ORM\ManyToOne(targetEntity=Author::class) */
    public $author;
    /** @ORM\ManyToOne(targetEntity=Category::class) */
    public $category;
    /** @ORM\Column(type="string") */
    public $title;
}

/**
 * @ORM\Entity
 */
class MyAuthor
{
    /** @ORM\Id @ORM\Column(type="integer") @ORM\GeneratedValue */
    public $id;
}

/**
 * @ORM\Entity
 */
class MyCategory
{
    /** @ORM\Id @ORM\Column(type="integer") @ORM\GeneratedValue */
    public $id;
}

/**
 * @ORM\Entity
 */
class BlogPost
{
    /** @ORM\Id @ORM\Column(type="integer") @ORM\GeneratedValue */
    public $id;
    /** @ORM\ManyToOne(targetEntity=Author::class) */
    public $author;
    /** @ORM\ManyToOne(targetEntity=Category::class) */
    public $category;
}

/**
 * @ORM\Entity
 */
class Author
{
    /** @ORM\Id @ORM\Column(type="integer") @ORM\GeneratedValue */
    public $id;
    /** @ORM\Column(type="string") */
    public $name;
}

/**
 * @ORM\Entity
 */
class Person
{
    /** @ORM\Id @ORM\Column(type="integer") @ORM\GeneratedValue */
    public $id;
    /** @ORM\Column(type="string") */
    public $name;
    /** @ORM\Column(type="string") */
    public $biography;
}

/**
 * @ORM\Entity
 */
class Category
{
    /** @ORM\Id @ORM\Column(type="integer") @ORM\GeneratedValue */
    public $id;
}

/** @ORM\Entity @ORM\Table(name="groups") */
class Group
{
    /** @ORM\Id @ORM\Column(type="integer") @ORM\GeneratedValue */
    public $id;
    /** @ORM\ManyToMany(targetEntity=User::class, mappedBy="groups") */
    public $users;
}

/** @ORM\Entity */
class User
{
    /** @ORM\Id @ORM\Column(type="integer") @ORM\GeneratedValue */
    public $id;
    /**
     * @ORM\ManyToMany(targetEntity=Group::class, inversedBy="users")
     * @ORM\JoinTable(
     * name="user_group",
     * joinColumns = {@ORM\JoinColumn(name="user_id", referencedColumnName="id")},
     * inverseJoinColumns = {@ORM\JoinColumn(name="group_id", referencedColumnName="id")}
     * )
     */
    public $groups;
    /** @ORM\OneToOne(targetEntity=Avatar::class, mappedBy="user") */
    public $avatar;
}

/** @ORM\Entity */
class Avatar
{
    /** @ORM\Id @ORM\Column(type="integer") @ORM\GeneratedValue */
    public $id;
    /**
     * @ORM\OneToOne(targetEntity=User::class, inversedBy="avatar")
     * @ORM\JoinColumn(name="user_id", referencedColumnName="id")
     */
    public $user;
    /** @ORM\Column(type="string", length=255) */
    public $image;
    /** @ORM\Column(type="integer") */
    public $image_height;
    /** @ORM\Column(type="integer") */
    public $image_width;
    /** @ORM\Column(type="string", length=255) */
    public $image_alt_desc;
}

/** @ORM\MappedSuperclass */
abstract class Identified
{
    /** @ORM\Id @ORM\Column(type="integer") @ORM\GeneratedValue */
    private $id;

    public function getId()
    {
        return $this->id;
    }
}

/** @ORM\Entity */
class Banner extends Identified
{
    /** @ORM\Column(type="string") */
    public $name;
}
