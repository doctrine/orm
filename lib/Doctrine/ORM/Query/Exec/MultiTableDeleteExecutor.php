<?php

declare(strict_types=1);

namespace Doctrine\ORM\Query\Exec;

use Doctrine\DBAL\Connection;
use Doctrine\ORM\Query\AST;
use Doctrine\ORM\Query\AST\Node;
use Doctrine\ORM\Query\SqlWalker;
use Throwable;
use function array_keys;
use function array_map;
use function array_merge;
use function array_reverse;
use function implode;
use function sprintf;

/**
 * Executes the SQL statements for bulk DQL DELETE statements on classes in
 * Class Table Inheritance (JOINED).
 */
class MultiTableDeleteExecutor extends AbstractSqlExecutor
{
    /** @var string */
    private $createTempTableSql;

    /** @var string */
    private $dropTempTableSql;

    /** @var string */
    private $insertSql;

    /**
     * Initializes a new <tt>MultiTableDeleteExecutor</tt>.
     *
     * {@internal Any SQL construction and preparation takes place in the constructor for
     *            best performance. With a query cache the executor will be cached. }}
     *
     * @param Node      $AST       The root AST node of the DQL query.
     * @param SqlWalker $sqlWalker The walker used for SQL generation from the AST.
     */
    public function __construct(AST\Node $AST, $sqlWalker)
    {
        $em       = $sqlWalker->getEntityManager();
        $conn     = $em->getConnection();
        $platform = $conn->getDatabasePlatform();

        $primaryClass    = $em->getClassMetadata($AST->deleteClause->abstractSchemaName);
        $primaryDqlAlias = $AST->deleteClause->aliasIdentificationVariable;
        $rootClass       = $em->getClassMetadata($primaryClass->getRootClassName());

        $tempTable        = $platform->getTemporaryTableName($rootClass->getTemporaryIdTableName());
        $idColumns        = $rootClass->getIdentifierColumns($em);
        $idColumnNameList = implode(', ', array_keys($idColumns));

        // 1. Create an INSERT INTO temptable ... SELECT identifiers WHERE $AST->getWhereClause()
        $sqlWalker->setSQLTableAlias($primaryClass->getTableName(), 'i0', $primaryDqlAlias);

        $this->insertSql = 'INSERT INTO ' . $tempTable . ' (' . $idColumnNameList . ')'
                . ' SELECT i0.' . implode(', i0.', array_keys($idColumns));

        $rangeDecl        = new AST\RangeVariableDeclaration($primaryClass->getClassName(), $primaryDqlAlias);
        $fromClause       = new AST\FromClause([new AST\IdentificationVariableDeclaration($rangeDecl, null, [])]);
        $this->insertSql .= $sqlWalker->walkFromClause($fromClause);

        // Append WHERE clause, if there is one.
        if ($AST->whereClause) {
            $this->insertSql .= $sqlWalker->walkWhereClause($AST->whereClause);
        }

        // 2. Create statement used in DELETE ... WHERE ... IN (subselect)
        $deleteSQLTemplate = sprintf(
            'DELETE FROM %%s WHERE (%s) IN (SELECT %s FROM %s)',
            $idColumnNameList,
            $idColumnNameList,
            $tempTable
        );

        // 3. Create and store DELETE statements
        $hierarchyClasses = array_merge(
            array_map(
                static function ($className) use ($em) {
                    return $em->getClassMetadata($className);
                },
                array_reverse($primaryClass->getSubClasses())
            ),
            [$primaryClass],
            $primaryClass->getAncestorsIterator()->getArrayCopy()
        );

        foreach ($hierarchyClasses as $class) {
            $this->sqlStatements[] = sprintf($deleteSQLTemplate, $class->table->getQuotedQualifiedName($platform));
        }

        // 4. Store DDL for temporary identifier table.
        $columnDefinitions = [];

        foreach ($idColumns as $columnName => $column) {
            $columnDefinitions[$columnName] = [
                'notnull' => true,
                'type'    => $column->getType(),
            ];
        }

        $this->createTempTableSql = $platform->getCreateTemporaryTableSnippetSQL() . ' ' . $tempTable . ' ('
                . $platform->getColumnDeclarationListSQL($columnDefinitions) . ')';

        $this->dropTempTableSql = $platform->getDropTemporaryTableSQL($tempTable);
    }

    /**
     * {@inheritDoc}
     */
    public function execute(Connection $conn, array $params, array $types)
    {
        // Create temporary id table
        $conn->executeUpdate($this->createTempTableSql);

        try {
            // Insert identifiers
            $numDeleted = $conn->executeUpdate($this->insertSql, $params, $types);

            // Execute DELETE statements
            foreach ($this->sqlStatements as $sql) {
                $conn->executeUpdate($sql);
            }
        } catch (Throwable $exception) {
            // FAILURE! Drop temporary table to avoid possible collisions
            $conn->executeUpdate($this->dropTempTableSql);

            // Re-throw exception
            throw $exception;
        }

        // Drop temporary table
        $conn->executeUpdate($this->dropTempTableSql);

        return $numDeleted;
    }
}
