<?php

declare(strict_types=1);

namespace Doctrine\ORM\Annotation;

/**
 * @Annotation
 * @Target("CLASS")
 */
final class ChangeTrackingPolicy implements Annotation
{
    /**
     * The change tracking policy.
     *
     * @var string
     * @Enum({"DEFERRED_IMPLICIT", "DEFERRED_EXPLICIT", "NOTIFY"})
     */
    public $value;
}
