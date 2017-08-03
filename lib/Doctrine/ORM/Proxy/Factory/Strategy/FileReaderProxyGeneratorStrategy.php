<?php


declare(strict_types=1);

namespace Doctrine\ORM\Proxy\Factory\Strategy;

use Doctrine\ORM\Proxy\Factory\ProxyDefinition;

class FileReaderProxyGeneratorStrategy implements ProxyGeneratorStrategy
{
    /**
     * {@inheritdoc}
     */
    public function generate(string $filePath, ProxyDefinition $definition): void
    {
        require $filePath;
    }
}
