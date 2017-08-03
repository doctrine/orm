<?php


declare(strict_types=1);

namespace Doctrine\ORM\Proxy\Factory\Strategy;

use Doctrine\ORM\Proxy\Factory\ProxyDefinition;
use Doctrine\ORM\Proxy\Factory\ProxyGenerator;

class EvaluatingProxyGeneratorStrategy implements ProxyGeneratorStrategy
{
    /**
     * @var ProxyGenerator
     */
    private $generator;

    /**
     * EvaluatingProxyGeneratorStrategy constructor.
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

        eval($sourceCode);
    }
}
