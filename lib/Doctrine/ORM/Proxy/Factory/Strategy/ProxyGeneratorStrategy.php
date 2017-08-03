<?php


declare(strict_types=1);

namespace Doctrine\ORM\Proxy\Factory\Strategy;

use Doctrine\ORM\Proxy\Factory\ProxyDefinition;

interface ProxyGeneratorStrategy
{
    /**
     * @param string          $filePath
     * @param ProxyDefinition $definition
     *
     * @return void
     */
    public function generate(string $filePath, ProxyDefinition $definition) : void;
}
