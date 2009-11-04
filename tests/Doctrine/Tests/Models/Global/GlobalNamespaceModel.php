<?php

/**
 * @entity
 * @table(name="articles")
 */
class DoctrineGlobal_Article
{
    /**
     * @id
     * @column(type="int")
     */
    protected $id;

    /**
     * @column(type="string")
     */
    protected $headline;

    /**
     * @column(type="text")
     */
    protected $text;

    /**
     * @ManyToMany(targetEntity="DoctrineGlobal_User")
     * @JoinTable(name="author_articles",
     *      joinColumns={@JoinColumn(name="article_id", referencedColumnName="id")},
     *      inverseJoinColumns={@JoinColumn(name="author_id", referencedColumnName="id", unique=true)}
     * )
     */
    protected $author;

    /**
     * @ManyToMany(targetEntity="\DoctrineGlobal_User")
     * @JoinTable(name="editor_articles",
     *      joinColumns={@JoinColumn(name="article_id", referencedColumnName="id")},
     *      inverseJoinColumns={@JoinColumn(name="editor_id", referencedColumnName="id", unique=true)}
     * )
     */
    protected $editor;
}

/**
 * @Entity
 * @Table(name="users")
 */
class DoctrineGlobal_User
{
    /**
     * @Id
     * @column(type="integer")
     * @var int
     */
    private $id;

    /**
     * @column(type="string", length=64)
     * @var string
     */
    private $username;

    /**
     * @column(type="string", length=128)
     * @var string
     */
    private $email;
}