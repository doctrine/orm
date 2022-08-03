<?php

declare(strict_types=1);

namespace Doctrine\ORM\Query;

use Doctrine\ORM\AbstractQuery;
use Generator;

/**
 * Represents a chain of tree walkers that modify an AST and finally emit output.
 * Only the last walker in the chain can emit output. Any previous walkers can modify
 * the AST to influence the final output produced by the last walker.
 *
 * @psalm-import-type QueryComponent from Parser
 */
class TreeWalkerChain implements TreeWalker
{
    /**
     * The tree walkers.
     *
     * @var string[]
     * @psalm-var list<class-string<TreeWalker>>
     */
    private array $walkers = [];

    /**
     * {@inheritdoc}
     */
    public function __construct(
        private readonly AbstractQuery $query,
        private readonly ParserResult $parserResult,
        private array $queryComponents
    ) {
    }

    /**
     * Returns the internal queryComponents array.
     *
     * {@inheritDoc}
     */
    public function getQueryComponents(): array
    {
        return $this->queryComponents;
    }

    /**
     * Adds a tree walker to the chain.
     *
     * @param string $walkerClass The class of the walker to instantiate.
     * @psalm-param class-string<TreeWalker> $walkerClass
     */
    public function addTreeWalker(string $walkerClass): void
    {
        $this->walkers[] = $walkerClass;
    }

    public function walkSelectStatement(AST\SelectStatement $selectStatement): void
    {
        foreach ($this->getWalkers() as $walker) {
            $walker->walkSelectStatement($selectStatement);

            $this->queryComponents = $walker->getQueryComponents();
        }
    }

    public function walkUpdateStatement(AST\UpdateStatement $updateStatement): void
    {
        foreach ($this->getWalkers() as $walker) {
            $walker->walkUpdateStatement($updateStatement);
        }
    }

    public function walkDeleteStatement(AST\DeleteStatement $deleteStatement): void
    {
        foreach ($this->getWalkers() as $walker) {
            $walker->walkDeleteStatement($deleteStatement);
        }
    }

    /**
     * @psalm-return Generator<int, TreeWalker>
     */
    private function getWalkers(): Generator
    {
        foreach ($this->walkers as $walkerClass) {
            yield new $walkerClass($this->query, $this->parserResult, $this->queryComponents);
        }
    }
}
