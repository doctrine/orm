<?php

use Doctrine\ORM\Annotation as ORM;

/**
 * @ORM\Entity
 * @ORM\Table(name="articles")
 */
class DoctrineGlobal_Article
{
    /**
     * @ORM\Id
     * @ORM\Column(type="integer")
     */
    protected $id;

    /**
     * @ORM\Column(type="string")
     */
    protected $headline;

    /**
     * @ORM\Column(type="text")
     */
    protected $text;

    /**
     * @ORM\ManyToMany(targetEntity="DoctrineGlobal_User")
     * @ORM\JoinTable(name="author_articles",
     *      joinColumns={@ORM\JoinColumn(name="article_id", referencedColumnName="id")},
     *      inverseJoinColumns={@ORM\JoinColumn(name="author_id", referencedColumnName="id", unique=true)}
     * )
     */
    protected $author;

    /**
     * @ORM\ManyToMany(targetEntity="DoctrineGlobal_User")
     * @ORM\JoinTable(name="editor_articles",
     *      joinColumns={@ORM\JoinColumn(name="article_id", referencedColumnName="id")},
     *      inverseJoinColumns={@ORM\JoinColumn(name="editor_id", referencedColumnName="id", unique=true)}
     * )
     */
    protected $editor;
}

/**
 * @ORM\Entity
 * @ORM\Table(name="users")
 */
class DoctrineGlobal_User
{
    /**
     * @ORM\Id
     * @ORM\Column(type="integer")
     *
     * @var int
     */
    private $id;

    /**
     * @ORM\Column(type="string", length=64)
     *
     * @var string
     */
    private $username;

    /**
     * @ORM\Column(type="string", length=128)
     *
     * @var string
     */
    private $email;
}