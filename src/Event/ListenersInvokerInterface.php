<?php

declare(strict_types=1);

namespace Doctrine\ORM\Event;

// phpcs:ignore SlevomatCodingStandard.Classes.SuperfluousInterfaceNaming.SuperfluousSuffix
interface ListenersInvokerInterface
{
    /**
     * Invokes listener.
     *
     * @param mixed[] $args
     */
    public function invokeListener(object $instance, string $method, array $args): void;
}
