<?php

namespace Doctrine\ORM\Proxy;

/**
 * Marker interface for proxy classes.
 * 
 * @author Roman Borschel <roman@code-factory.org>
 * @since 2.0
 */
interface Proxy
{
    function __isInitialized__();
}