<?php

declare(strict_types=1);

namespace Doctrine\ORM\Mapping\Factory\Strategy;

use Doctrine\ORM\Mapping\Factory\ClassMetadataDefinition;
use Doctrine\ORM\Mapping\Factory\ClassMetadataGenerator;

class FileWriterClassMetadataGeneratorStrategy implements ClassMetadataGeneratorStrategy
{
    /**
     * @var ClassMetadataGenerator
     */
    private $generator;

    /**
     * FileWriterDefinitionGeneratorStrategy constructor.
     */
    public function __construct(ClassMetadataGenerator $generator)
    {
        $this->generator = $generator;
    }

    /**
     * {@inheritdoc}
     */
    public function generate(string $filePath, ClassMetadataDefinition $definition) : void
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
     * @throws \RuntimeException
     */
    private function ensureDirectoryIsReady(string $directory)
    {
        if (! is_dir($directory) && (@mkdir($directory, 0775, true) === false)) {
            throw new \RuntimeException(sprintf('Your metadata directory "%s" must be writable', $directory));
        }

        if (! is_writable($directory)) {
            throw new \RuntimeException(sprintf('Your proxy directory "%s" must be writable', $directory));
        }
    }
}
