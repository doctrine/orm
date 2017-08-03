<?php


declare(strict_types=1);

namespace Doctrine\ORM\Proxy\Factory\Strategy;

use Doctrine\ORM\Proxy\Factory\ProxyDefinition;

class ConditionalFileWriterProxyGeneratorStrategy extends FileWriterProxyGeneratorStrategy
{
    /**
     * {@inheritdoc}
     */
    public function generate(string $filePath, ProxyDefinition $definition): void
    {
        if (! file_exists($filePath)) {
            parent::generate($filePath, $definition);

            return;
        }

        require $filePath;
    }
}
