<?php

declare(strict_types=1);

namespace Doctrine\ORM\Mapping\Factory\Strategy;

use Doctrine\ORM\Mapping\Factory\ClassMetadataDefinition;
use Doctrine\ORM\Mapping\Factory\ClassMetadataGenerator;

class EvaluatingClassMetadataGeneratorStrategy implements ClassMetadataGeneratorStrategy
{
    /** @var ClassMetadataGenerator */
    private $generator;

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

        eval($sourceCode);
    }
}
