<?php

declare(strict_types=1);

namespace Doctrine\Tests\Models\ValueGenerators;

use Doctrine\ORM\EntityManagerInterface;
use Doctrine\ORM\Sequencing\Generator;

class BarGenerator implements Generator
{
    public const VALUE = 'bar';

    public function generate(EntityManagerInterface $em, $entity) : string
    {
        return self::VALUE;
    }

    public function isPostInsertGenerator() : bool
    {
        return false;
    }
}
