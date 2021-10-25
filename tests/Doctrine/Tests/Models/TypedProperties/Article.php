<?php

declare(strict_types=1);

namespace Doctrine\Tests\Models\TypedProperties;

use Doctrine\ORM\Mapping as ORM;

#[ORM\Entity, ORM\Table(name: 'cms_articles_enumed')]
class Article
{
    #[ORM\Id, ORM\Column, ORM\GeneratedValue]
    public int $id;

    #[ORM\Column]
    public ArticleStateEnum $enum;
}
