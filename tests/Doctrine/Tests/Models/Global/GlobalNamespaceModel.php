<?php

declare(strict_types=1);

use Doctrine\ORM\Annotation as ORM;

/**
 * @ORM\Entity
 * @ORM\Table(name="articles")
 */
class DoctrineGlobalArticle
{
    /**
     * @ORM\Id
     * @ORM\Column(type="integer")
     */
    protected $id;

    /** @ORM\Column(type="string") */
    protected $headline;

    /** @ORM\Column(type="text") */
    protected $text;

    /**
     * @ORM\ManyToMany(targetEntity=DoctrineGlobalUser::class)
     * @ORM\JoinTable(name="author_articles",
     *      joinColumns={@ORM\JoinColumn(name="article_id", referencedColumnName="id")},
     *      inverseJoinColumns={@ORM\JoinColumn(name="author_id", referencedColumnName="id", unique=true)}
     * )
     */
    protected $author;

    /**
     * @ORM\ManyToMany(targetEntity=DoctrineGlobalUser::class)
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
class DoctrineGlobalUser
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
