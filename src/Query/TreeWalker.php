<?php

declare(strict_types=1);

namespace Doctrine\ORM\Query;

use Doctrine\ORM\AbstractQuery;

/**
 * Interface for walkers of DQL ASTs (abstract syntax trees).
 *
 * @phpstan-import-type QueryComponent from Parser
 */
interface TreeWalker
{
    /**
     * Initializes TreeWalker with important information about the ASTs to be walked.
     *
     * @phpstan-param array<string, QueryComponent> $queryComponents The query components (symbol table).
     */
    public function __construct(AbstractQuery $query, ParserResult $parserResult, array $queryComponents);

    /**
     * Returns internal queryComponents array.
     *
     * @phpstan-return array<string, QueryComponent>
     */
    public function getQueryComponents(): array;

    /**
     * Walks down a SelectStatement AST node.
     */
    public function walkSelectStatement(AST\SelectStatement $selectStatement): void;

    /**
     * Walks down an UpdateStatement AST node.
     */
    public function walkUpdateStatement(AST\UpdateStatement $updateStatement): void;

    /**
     * Walks down a DeleteStatement AST node.
     */
    public function walkDeleteStatement(AST\DeleteStatement $deleteStatement): void;
}
