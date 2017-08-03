<?php


declare(strict_types=1);

namespace Doctrine\ORM\Proxy;

use Doctrine\ORM\Proxy\Factory\ProxyDefinition;

/**
 * Interface for proxy classes.
 *
 * @author Roman Borschel <roman@code-factory.org>
 * @since 2.0
 */
interface Proxy
{
    /**
     * Initializes this proxy if its not yet initialized.
     *
     * Acts as a no-op if already initialized.
     *
     * @return void
     */
    public function __load();

    /**
     * Returns whether this proxy is initialized or not.
     *
     * @return bool
     */
    public function __isInitialized();

    /**
     * Marks the proxy as initialized or not.
     *
     * @param boolean $initialized
     *
     * @return void
     */
    public function __setInitialized($initialized);
}
