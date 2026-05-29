<?php

declare(strict_types=1);

namespace Doctrine\Tests\Models\CMS;

use Doctrine\ORM\Mapping\Column;
use Doctrine\ORM\Mapping\Entity;
use Doctrine\ORM\Mapping\Id;
use Doctrine\ORM\Mapping\JoinColumn;
use Doctrine\ORM\Mapping\OneToOne;
use Doctrine\ORM\Mapping\Table;

#[Table(name: 'cms_article_associated_details')]
#[Entity]
class CmsArticleAssociatedDetail
{
    #[Id]
    #[OneToOne(targetEntity: 'CmsArticle')]
    #[JoinColumn(name: 'article_id', referencedColumnName: 'id')]
    public CmsArticle $article;

    /** @var string */
    #[Column(type: 'text')]
    public $detail;
}
