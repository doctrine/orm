<?php

declare(strict_types=1);

namespace Doctrine\Tests\Models\DDC117;

use Doctrine\ORM\Mapping\Entity;
use Doctrine\ORM\Mapping\Id;
use Doctrine\ORM\Mapping\JoinColumn;
use Doctrine\ORM\Mapping\ManyToOne;

/**
 * Foreign Key Entity without additional fields!
 */
#[Entity]
class DDC117Link
{
    public function __construct(
        #[Id]
        #[ManyToOne(targetEntity: 'DDC117Article', inversedBy: 'links')]
        #[JoinColumn(name: 'source_id', referencedColumnName: 'article_id')]
        public DDC117Article $source,
        #[Id]
        #[ManyToOne(targetEntity: 'DDC117Article')]
        #[JoinColumn(name: 'target_id', referencedColumnName: 'article_id')]
        public DDC117Article $target,
        $description,
    ) {
    }
}
