<?php

declare(strict_types=1);

namespace Doctrine\Tests\Models\DDC117;

use Doctrine\ORM\Mapping\Column;
use Doctrine\ORM\Mapping\Entity;
use Doctrine\ORM\Mapping\GeneratedValue;
use Doctrine\ORM\Mapping\Id;
use Doctrine\ORM\Mapping\JoinColumn;
use Doctrine\ORM\Mapping\ManyToOne;

#[Entity]
class DDC117ApproveChanges
{
    #[Id]
    #[Column(type: 'integer')]
    #[GeneratedValue]
    private int $id;

    public function __construct(
        #[ManyToOne(targetEntity: 'DDC117ArticleDetails')]
        #[JoinColumn(name: 'details_id', referencedColumnName: 'article_id')]
        private DDC117ArticleDetails $articleDetails,
        #[JoinColumn(name: 'source_id', referencedColumnName: 'source_id')]
        #[JoinColumn(name: 'target_id', referencedColumnName: 'target_id')]
        #[ManyToOne(targetEntity: 'DDC117Reference')]
        private DDC117Reference $reference,
        #[JoinColumn(name: 'trans_article_id', referencedColumnName: 'article_id')]
        #[JoinColumn(name: 'trans_language', referencedColumnName: 'language')]
        #[ManyToOne(targetEntity: 'DDC117Translation')]
        private DDC117Translation $translation,
    ) {
    }

    public function getId(): int
    {
        return $this->id;
    }

    public function getArticleDetails(): DDC117ArticleDetails
    {
        return $this->articleDetails;
    }

    public function getReference(): DDC117Reference
    {
        return $this->reference;
    }

    public function getTranslation(): DDC117Translation
    {
        return $this->translation;
    }
}
