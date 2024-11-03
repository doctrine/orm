<?php

declare(strict_types=1);

use Doctrine\Common\Collections\Collection;
use Doctrine\ORM\Mapping\Column;
use Doctrine\ORM\Mapping\Entity;
use Doctrine\ORM\Mapping\Id;
use Doctrine\ORM\Mapping\InverseJoinColumn;
use Doctrine\ORM\Mapping\JoinColumn;
use Doctrine\ORM\Mapping\JoinTable;
use Doctrine\ORM\Mapping\ManyToMany;
use Doctrine\ORM\Mapping\Table;

#[Table(name: 'articles')]
#[Entity]
class DoctrineGlobalArticle
{
    /** @var int */
    #[Id]
    #[Column(type: 'integer')]
    protected $id;

    /** @var string */
    #[Column(type: 'string', length: 255)]
    protected $headline;

    /** @var string */
    #[Column(type: 'text')]
    protected $text;

    /** @phpstan-var Collection<int, DoctrineGlobalUser> */
    #[JoinTable(name: 'author_articles')]
    #[JoinColumn(name: 'article_id', referencedColumnName: 'id')]
    #[InverseJoinColumn(name: 'author_id', referencedColumnName: 'id', unique: true)]
    #[ManyToMany(targetEntity: 'DoctrineGlobalUser')]
    protected $author;

    /** @phpstan-var Collection<int, DoctrineGlobalUser> */
    #[JoinTable(name: 'editor_articles')]
    #[JoinColumn(name: 'article_id', referencedColumnName: 'id')]
    #[InverseJoinColumn(name: 'editor_id', referencedColumnName: 'id', unique: true)]
    #[ManyToMany(targetEntity: 'DoctrineGlobalUser')]
    protected $editor;
}

#[Table(name: 'users')]
#[Entity]
class DoctrineGlobalUser
{
    #[Id]
    #[Column(type: 'integer')]
    private int $id;

    #[Column(type: 'string', length: 64)]
    private string $username;

    #[Column(type: 'string', length: 128)]
    private string $email;
}
