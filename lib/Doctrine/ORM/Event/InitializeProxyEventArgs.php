<?php

declare(strict_types=1);

namespace Doctrine\ORM\Event;

use Doctrine\Common\EventArgs;
use Doctrine\Common\Proxy\Proxy;

/**
 * Provides event arguments for the initializeProxy event.
 */
class InitializeProxyEventArgs extends EventArgs
{
    /** @var Proxy */
    private $proxy;

    public function __construct(Proxy $proxy)
    {
        $this->proxy = $proxy;
    }

    /**
     * Retrieve associated proxy.
     */
    public function getProxy() : Proxy
    {
        return $this->proxy;
    }
}
