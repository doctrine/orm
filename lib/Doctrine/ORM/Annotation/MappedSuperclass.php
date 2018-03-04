<?php

declare(strict_types=1);

namespace Doctrine\ORM\Annotation;

/**
 * @Annotation
 * @Target("CLASS")
 */
final class MappedSuperclass implements Annotation
{
    /** @var string */
    public $repositoryClass;
}
