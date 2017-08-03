<?php


declare(strict_types=1);

namespace Doctrine\ORM\Proxy\Factory;

/**
 * Interface ProxyResolver
 *
 * @package Doctrine\ORM\Proxy\Factory
 *
 * @since 3.0
 * @author Guilherme Blanco <guilhermeblanco@hotmail.com>
 */
interface ProxyResolver
{
    /**
     * @param string $className
     *
     * @return string
     */
    public function resolveProxyClassName(string $className) : string;

    /**
     * @param string $className
     *
     * @return string
     */
    public function resolveProxyClassPath(string $className) : string;
}
