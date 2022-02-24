<?php

declare(strict_types=1);

namespace Doctrine\ORM\Mapping;

use Attribute;
use Doctrine\Common\Annotations\Annotation\NamedArgumentConstructor;
use Doctrine\Persistence\ObjectRepository;

/**
 * @Annotation
 * @NamedArgumentConstructor()
 * @Target("CLASS")
 */
#[Attribute(Attribute::TARGET_CLASS)]
final class Entity implements Annotation
{
    /**
     * @var string|null
     * @psalm-var class-string<ObjectRepository>|null
     */
    public $repositoryClass;

    /** @var bool */
    public $readOnly = false;

    /**
     * @psalm-param class-string<ObjectRepository>|null $repositoryClass
     */
    public function __construct(?string $repositoryClass = null, bool $readOnly = false)
    {
        $this->repositoryClass = $repositoryClass;
        $this->readOnly        = $readOnly;
    }
}
