<?php
/*
 *  $Id$
 *
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

use Doctrine\ORM\Query\AST;

/**
 * Executes the SQL statements for bulk DQL UPDATE statements on classes in
 * Class Table Inheritance (JOINED).
 *
 * @author      Roman Borschel <roman@code-factory.org>
 * @license     http://www.opensource.org/licenses/lgpl-license.php LGPL
 * @link        http://www.doctrine-project.org
 * @since       2.0
 * @version     $Revision$
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
        $updateClause = $AST->updateClause;

        $primaryClass = $sqlWalker->getEntityManager()->getClassMetadata($updateClause->abstractSchemaName);
        $rootClass = $em->getClassMetadata($primaryClass->rootEntityName);

        $updateItems = $updateClause->updateItems;

        $tempTable = $rootClass->getTemporaryIdTableName();
        $idColumnNames = $rootClass->getIdentifierColumnNames();
        $idColumnList = implode(', ', $idColumnNames);

        // 1. Create an INSERT INTO temptable ... SELECT identifiers WHERE $AST->getWhereClause()
        $this->_insertSql = 'INSERT INTO ' . $tempTable . ' (' . $idColumnList . ')'
                . ' SELECT t0.' . implode(', t0.', $idColumnNames);
        $sqlWalker->setSqlTableAlias($primaryClass->primaryTable['name'] . $updateClause->aliasIdentificationVariable, 't0');
        $rangeDecl = new AST\RangeVariableDeclaration($primaryClass, $updateClause->aliasIdentificationVariable);
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
            $tableName = $class->primaryTable['name'];
            $updateSql = 'UPDATE ' . $conn->quoteIdentifier($tableName) . ' SET ';

            foreach ($updateItems as $updateItem) {
                $field = $updateItem->field;
                if (isset($class->fieldMappings[$field]) && ! isset($class->fieldMappings[$field]['inherited'])) {
                    $newValue = $updateItem->newValue;
                    
                    if ( ! $affected) {
                        $affected = true;
                        ++$i;
                    } else {
                        $updateSql .= ', ';
                    }
                    
                    $updateSql .= $sqlWalker->walkUpdateItem($updateItem);
                    
                    //FIXME: parameters can be more deeply nested. traverse the tree.
                    if ($newValue instanceof AST\InputParameter) {
                        $paramKey = $newValue->name;
                        $this->_sqlParameters[$i][] = $sqlWalker->getQuery()->getParameter($paramKey);
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
                'type' => \Doctrine\DBAL\Types\Type::getType($rootClass->getTypeOfColumn($idColumnName))
            );
        }
        $this->_createTempTableSql = 'CREATE TEMPORARY TABLE ' . $tempTable . ' ('
                . $conn->getDatabasePlatform()->getColumnDeclarationListSql($columnDefinitions)
                . ', PRIMARY KEY(' . $idColumnList . '))';
        $this->_dropTempTableSql = 'DROP TABLE ' . $tempTable;
    }

    /**
     * Executes all sql statements.
     *
     * @param Doctrine_Connection $conn  The database connection that is used to execute the queries.
     * @param array $params  The parameters.
     * @override
     */
    public function execute(\Doctrine\DBAL\Connection $conn, array $params)
    {
        $numUpdated = 0;

        // Create temporary id table
        $conn->executeUpdate($this->_createTempTableSql);

        // Insert identifiers. Parameters from the update clause are cut off.
        $numUpdated = $conn->executeUpdate($this->_insertSql, array_slice($params, $this->_numParametersInUpdateClause));

        // Execute UPDATE statements
        for ($i=0, $count=count($this->_sqlStatements); $i<$count; ++$i) {
            $conn->executeUpdate($this->_sqlStatements[$i], $this->_sqlParameters[$i]);
        }

        // Drop temporary table
        $conn->executeUpdate($this->_dropTempTableSql);

        return $numUpdated;
    }
}