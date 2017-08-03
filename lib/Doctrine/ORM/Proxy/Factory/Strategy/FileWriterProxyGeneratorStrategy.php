<?php


declare(strict_types=1);

namespace Doctrine\ORM\Proxy\Factory\Strategy;

use Doctrine\ORM\Proxy\Factory\ProxyDefinition;
use Doctrine\ORM\Proxy\Factory\ProxyGenerator;

class FileWriterProxyGeneratorStrategy implements ProxyGeneratorStrategy
{
    /**
     * @var ProxyGenerator
     */
    private $generator;

    /**
     * FileWriterProxyGeneratorStrategy constructor.
     *
     * @param ProxyGenerator $generator
     */
    public function __construct(ProxyGenerator $generator)
    {
        $this->generator = $generator;
    }

    /**
     * {@inheritdoc}
     */
    public function generate(string $filePath, ProxyDefinition $definition): void
    {
        $sourceCode = $this->generator->generate($definition);

        $this->ensureDirectoryIsReady(dirname($filePath));

        $tmpFileName = $filePath . '.' . uniqid('', true);

        file_put_contents($tmpFileName, $sourceCode);
        @chmod($tmpFileName, 0664);
        rename($tmpFileName, $filePath);

        require $filePath;
    }

    /**
     * @param string $directory
     *
     * @throws \RuntimeException
     */
    private function ensureDirectoryIsReady(string $directory)
    {
        if (! is_dir($directory) && (false === @mkdir($directory, 0775, true))) {
            throw new \RuntimeException(sprintf('Your proxy directory "%s" must be writable', $directory));
        }

        if (! is_writable($directory)) {
            throw new \RuntimeException(sprintf('Your proxy directory "%s" must be writable', $directory));
        }
    }
}
