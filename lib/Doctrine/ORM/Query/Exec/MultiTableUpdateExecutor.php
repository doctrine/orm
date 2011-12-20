<?php
/*
 * THIS SOFTWARE IS PROVIDED BY THE COPYRIGHT HOLDERS AND CONTRIBUTORS
 * "AS IS" AND ANY EXPRESS OR IMPLIED WARRANTIES, INCLUDING, BUT NOT
 * LIMITED TO, THE IMPLIED WARRANTIES OF MERCHANTABILITY AND FITNESS FOR
 * A PARTICULAR PURPOSE ARE DISCLAIMED. IN NO EVENT SHALL THE COPYRIGHT
 * OWNER OR CONTRIBUTORS BE LIABLE FOR ANY DIRECT, INDIRECT, INCIDENTAL,
 * SPECIAL, EXEMPLARY, OR CONSEQUENTIAL DAMAGES (INCLUDING, BUT NOT
 * LIMITED TO, PROCUREMENT OF SUBSTITUTE GOODS OR SERVICES; LOSS OF USE,
 * DATA, OR PROFITS; OR BUSINESS INTERRUPTION) HOWEVER CAUSED AND ON ANY
 * THEORY OF LIABILITY, WHETHER IN CONTRACT, STRICT LIABILITY, OR TORT
 * (INCLUDING NEGLIGENCE OR OTHERWISE) ARISING IN ANY WAY OUT OF THE USE
 * OF THIS SOFTWARE, EVEN IF ADVISED OF THE POSSIBILITY OF SUCH DAMAGE.
 *
 * This software consists of voluntary contributions made by many individuals
 * and is licensed under the LGPL. For more information, see
 * <http://www.doctrine-project.org>.
 */

namespace Doctrine\ORM\Query\Exec;

use Doctrine\DBAL\Connection,
    Doctrine\DBAL\Types\Type,
    Doctrine\ORM\Query\AST;

/**
 * Executes the SQL statements for bulk DQL UPDATE statements on classes in
 * Class Table Inheritance (JOINED).
 *
 * @author Roman Borschel <roman@code-factory.org>
 * @since 2.0
 */
class MultiTableUpdateExecutor extends AbstractSqlExecutor
{
    private $_createTempTableSql;
    private $_dropTempTableSql;
    private $_insertSql;
    private $_sqlParameters = array();
    private $_numParametersInUpdateClause = 0;

    /**
     * Initializes a new <tt>MultiTableUpdateExecutor</tt>.
     *
     * @param Node $AST The root AST node of the DQL query.
     * @param SqlWalker $sqlWalker The walker used for SQL generation from the AST.
     * @internal Any SQL construction and preparation takes place in the constructor for
     *           best performance. With a query cache the executor will be cached.
     */
    public function __construct(AST\Node $AST, $sqlWalker)
    {
        $em = $sqlWalker->getEntityManager();
        $conn = $em->getConnection();
        $platform = $conn->getDatabasePlatform();

        $updateClause = $AST->updateClause;

        $primaryClass = $sqlWalker->getEntityManager()->getClassMetadata($updateClause->abstractSchemaName);
        $rootClass = $em->getClassMetadata($primaryClass->rootEntityName);

        $updateItems = $updateClause->updateItems;

        $tempTable = $platform->getTemporaryTableName($rootClass->getTemporaryIdTableName());
        $idColumnNames = $rootClass->getIdentifierColumnNames();
        $idColumnList = implode(', ', $idColumnNames);

        // 1. Create an INSERT INTO temptable ... SELECT identifiers WHERE $AST->getWhereClause()
        $sqlWalker->setSQLTableAlias($primaryClass->getTableName(), 't0', $updateClause->aliasIdentificationVariable);

        $this->_insertSql = 'INSERT INTO ' . $tempTable . ' (' . $idColumnList . ')'
                . ' SELECT t0.' . implode(', t0.', $idColumnNames);

        $rangeDecl = new AST\RangeVariableDeclaration($primaryClass->name, $updateClause->aliasIdentificationVariable);
        $fromClause = new AST\FromClause(array(new AST\IdentificationVariableDeclaration($rangeDecl, null, array())));

        $this->_insertSql .= $sqlWalker->walkFromClause($fromClause);

        // 2. Create ID subselect statement used in UPDATE ... WHERE ... IN (subselect)
        $idSubselect = 'SELECT ' . $idColumnList . ' FROM ' . $tempTable;

        // 3. Create and store UPDATE statements
        $classNames = array_merge($primaryClass->parentClasses, array($primaryClass->name), $primaryClass->subClasses);
        $i = -1;

        foreach (array_reverse($classNames) as $className) {
            $affected = false;
            $class = $em->getClassMetadata($className);
            $updateSql = 'UPDATE ' . $class->getQuotedTableName($platform) . ' SET ';

            foreach ($updateItems as $updateItem) {
                $field = $updateItem->pathExpression->field;

                if (isset($class->fieldMappings[$field]) && ! isset($class->fieldMappings[$field]['inherited']) ||
                    isset($class->associationMappings[$field]) && ! isset($class->associationMappings[$field]['inherited'])) {
                    $newValue = $updateItem->newValue;

                    if ( ! $affected) {
                        $affected = true;
                        ++$i;
                    } else {
                        $updateSql .= ', ';
                    }

                    $updateSql .= $sqlWalker->walkUpdateItem($updateItem);

                    //FIXME: parameters can be more deeply nested. traverse the tree.
                    //FIXME (URGENT): With query cache the parameter is out of date. Move to execute() stage.
                    if ($newValue instanceof AST\InputParameter) {
                        $paramKey = $newValue->name;
                        $this->_sqlParameters[$i]['parameters'][] = $sqlWalker->getQuery()->getParameter($paramKey);
                        $this->_sqlParameters[$i]['types'][] = $sqlWalker->getQuery()->getParameterType($paramKey);

                        ++$this->_numParametersInUpdateClause;
                    }
                }
            }

            if ($affected) {
                $this->_sqlStatements[$i] = $updateSql . ' WHERE (' . $idColumnList . ') IN (' . $idSubselect . ')';
            }
        }

        // Append WHERE clause to insertSql, if there is one.
        if ($AST->whereClause) {
            $this->_insertSql .= $sqlWalker->walkWhereClause($AST->whereClause);
        }

        // 4. Store DDL for temporary identifier table.
        $columnDefinitions = array();

        foreach ($idColumnNames as $idColumnName) {
            $columnDefinitions[$idColumnName] = array(
                'notnull' => true,
                'type' => Type::getType($rootClass->getTypeOfColumn($idColumnName))
            );
        }

        $this->_createTempTableSql = $platform->getCreateTemporaryTableSnippetSQL() . ' ' . $tempTable . ' ('
                . $platform->getColumnDeclarationListSQL($columnDefinitions) . ')';

        $this->_dropTempTableSql = $platform->getDropTemporaryTableSQL($tempTable);
    }

    /**
     * {@inheritDoc}
     */
    public function execute(Connection $conn, array $params, array $types)
    {
        $numUpdated = 0;

        // Create temporary id table
        $conn->executeUpdate($this->_createTempTableSql);

        // Insert identifiers. Parameters from the update clause are cut off.
        $numUpdated = $conn->executeUpdate(
            $this->_insertSql,
            array_slice($params, $this->_numParametersInUpdateClause),
            array_slice($types, $this->_numParametersInUpdateClause)
        );

        // Execute UPDATE statements
        for ($i=0, $count=count($this->_sqlStatements); $i<$count; ++$i) {
            $parameters = array();
            $types      = array();

            if (isset($this->_sqlParameters[$i])) {
                $parameters = isset($this->_sqlParameters[$i]['parameters']) ? $this->_sqlParameters[$i]['parameters'] : array();
                $types = isset($this->_sqlParameters[$i]['types']) ? $this->_sqlParameters[$i]['types'] : array();
            }

            $conn->executeUpdate($this->_sqlStatements[$i], $parameters, $types);
        }

        // Drop temporary table
        $conn->executeUpdate($this->_dropTempTableSql);

        return $numUpdated;
    }
}