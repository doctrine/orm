<?php

declare(strict_types=1);

namespace Doctrine\ORM\Query\Exec;

use Doctrine\DBAL\Connection;
use Doctrine\ORM\Mapping\ColumnMetadata;
use Doctrine\ORM\Query\AST;
use Doctrine\ORM\Query\ParameterTypeInferer;
use Doctrine\ORM\Utility\PersisterHelper;
use Throwable;

/**
 * Executes the SQL statements for bulk DQL UPDATE statements on classes in
 * Class Table Inheritance (JOINED).
 *
 * @author Roman Borschel <roman@code-factory.org>
 * @since 2.0
 */
class MultiTableUpdateExecutor extends AbstractSqlExecutor
{
    /**
     * @var string
     */
    private $createTempTableSql;

    /**
     * @var string
     */
    private $dropTempTableSql;

    /**
     * @var string
     */
    private $insertSql;

    /**
     * @var array
     */
    private $sqlParameters = [];

    /**
     * @var int
     */
    private $numParametersInUpdateClause = 0;

    /**
     * Initializes a new <tt>MultiTableUpdateExecutor</tt>.
     *
     * Internal note: Any SQL construction and preparation takes place in the constructor for
     *                best performance. With a query cache the executor will be cached.
     *
     * @param \Doctrine\ORM\Query\AST\Node  $AST The root AST node of the DQL query.
     * @param \Doctrine\ORM\Query\SqlWalker $sqlWalker The walker used for SQL generation from the AST.
     */
    public function __construct(AST\Node $AST, $sqlWalker)
    {
        $em             = $sqlWalker->getEntityManager();
        $conn           = $em->getConnection();
        $platform       = $conn->getDatabasePlatform();

        $updateClause   = $AST->updateClause;
        $primaryClass   = $sqlWalker->getEntityManager()->getClassMetadata($updateClause->abstractSchemaName);
        $rootClass      = $em->getClassMetadata($primaryClass->getRootClassName());

        $updateItems    = $updateClause->updateItems;

        $tempTable         = $platform->getTemporaryTableName($rootClass->getTemporaryIdTableName());
        $idColumns         = $rootClass->getIdentifierColumns($em);
        $idColumnNameList  = implode(', ', array_keys($idColumns));

        // 1. Create an INSERT INTO temptable ... SELECT identifiers WHERE $AST->getWhereClause()
        $sqlWalker->setSQLTableAlias($primaryClass->getTableName(), 'i0', $updateClause->aliasIdentificationVariable);

        $this->insertSql = 'INSERT INTO ' . $tempTable . ' (' . $idColumnNameList . ')'
                . ' SELECT i0.' . implode(', i0.', array_keys($idColumns));

        $rangeDecl = new AST\RangeVariableDeclaration($primaryClass->getClassName(), $updateClause->aliasIdentificationVariable);
        $fromClause = new AST\FromClause([new AST\IdentificationVariableDeclaration($rangeDecl, null, [])]);

        $this->insertSql .= $sqlWalker->walkFromClause($fromClause);

        // 2. Create ID subselect statement used in UPDATE ... WHERE ... IN (subselect)
        $idSubselect = 'SELECT ' . $idColumnNameList . ' FROM ' . $tempTable;

        // 3. Create and store UPDATE statements
        $classNames = array_merge(
            $primaryClass->getParentClasses(),
            [$primaryClass->getClassName()],
            $primaryClass->getSubClasses()
        );

        $i = -1;

        foreach (array_reverse($classNames) as $className) {
            $affected  = false;
            $class     = $em->getClassMetadata($className);
            $tableName = $class->table->getQuotedQualifiedName($platform);
            $updateSql = 'UPDATE ' . $tableName . ' SET ';

            foreach ($updateItems as $updateItem) {
                $field    = $updateItem->pathExpression->field;
                $property = $class->getProperty($field);

                if ($property && ! $class->isInheritedProperty($field)) {
                    $newValue = $updateItem->newValue;

                    if ( ! $affected) {
                        $affected = true;
                        ++$i;
                    } else {
                        $updateSql .= ', ';
                    }

                    $updateSql .= $sqlWalker->walkUpdateItem($updateItem);

                    if ($newValue instanceof AST\InputParameter) {
                        $this->sqlParameters[$i][] = $newValue->name;

                        ++$this->numParametersInUpdateClause;
                    }
                }
            }

            if ($affected) {
                $this->sqlStatements[$i] = $updateSql . ' WHERE (' . $idColumnNameList . ') IN (' . $idSubselect . ')';
            }
        }

        // Append WHERE clause to insertSql, if there is one.
        if ($AST->whereClause) {
            $this->insertSql .= $sqlWalker->walkWhereClause($AST->whereClause);
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
            // Insert identifiers. Parameters from the update clause are cut off.
            $numUpdated = $conn->executeUpdate(
                $this->insertSql,
                array_slice($params, $this->numParametersInUpdateClause),
                array_slice($types, $this->numParametersInUpdateClause)
            );

            // Execute UPDATE statements
            foreach ($this->sqlStatements as $key => $statement) {
                $paramValues = [];
                $paramTypes  = [];

                if (isset($this->sqlParameters[$key])) {
                    foreach ($this->sqlParameters[$key] as $parameterKey => $parameterName) {
                        $paramValues[] = $params[$parameterKey];
                        $paramTypes[]  = $types[$parameterKey] ?? ParameterTypeInferer::inferType($params[$parameterKey]);
                    }
                }

                $conn->executeUpdate($statement, $paramValues, $paramTypes);
            }
        } catch (Throwable $exception) {
            // FAILURE! Drop temporary table to avoid possible collisions
            $conn->executeUpdate($this->dropTempTableSql);

            // Re-throw exception
            throw $exception;
        }

        // Drop temporary table
        $conn->executeUpdate($this->dropTempTableSql);

        return $numUpdated;
    }
}
