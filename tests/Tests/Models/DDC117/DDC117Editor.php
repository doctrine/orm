<?php

declare(strict_types=1);

namespace Doctrine\Tests\Models\DDC117;

use Doctrine\Common\Collections\ArrayCollection;
use Doctrine\Common\Collections\Collection;
use Doctrine\ORM\Mapping\Column;
use Doctrine\ORM\Mapping\Entity;
use Doctrine\ORM\Mapping\GeneratedValue;
use Doctrine\ORM\Mapping\Id;
use Doctrine\ORM\Mapping\InverseJoinColumn;
use Doctrine\ORM\Mapping\JoinColumn;
use Doctrine\ORM\Mapping\JoinTable;
use Doctrine\ORM\Mapping\ManyToMany;
use Doctrine\ORM\Mapping\ManyToOne;

#[Entity]
class DDC117Editor
{
    /** @var int */
    #[Id]
    #[Column(type: 'integer')]
    #[GeneratedValue]
    public $id;

    /** @phpstan-var Collection<int, DDC117Translation> */
    #[JoinTable]
    #[JoinColumn(name: 'editor_id', referencedColumnName: 'id')]
    #[InverseJoinColumn(name: 'article_id', referencedColumnName: 'article_id')]
    #[InverseJoinColumn(name: 'language', referencedColumnName: 'language')]
    #[ManyToMany(targetEntity: 'DDC117Translation', inversedBy: 'reviewedByEditors')]
    public $reviewingTranslations;

    /** @var DDC117Translation */
    #[JoinColumn(name: 'lt_article_id', referencedColumnName: 'article_id')]
    #[JoinColumn(name: 'lt_language', referencedColumnName: 'language')]
    #[ManyToOne(targetEntity: 'DDC117Translation', inversedBy: 'lastTranslatedBy')]
    public $lastTranslation;

    public function __construct(
        #[Column(type: 'string', length: 255)]
        public string|null $name = '',
    ) {
        $this->reviewingTranslations = new ArrayCollection();
    }

    public function addLastTranslation(DDC117Translation $t): void
    {
        $this->lastTranslation = $t;
        $t->lastTranslatedBy[] = $this;
    }
}
