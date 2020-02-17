<?php

declare(strict_types=1);

namespace Doctrine\ORM\Annotation;

/**
 * @Annotation
 * @Target("CLASS")
 */
final class Entity implements Annotation
{
    /** @var string */
    public $repositoryClass;

    /** @var bool */
    public $readOnly = false;
}
