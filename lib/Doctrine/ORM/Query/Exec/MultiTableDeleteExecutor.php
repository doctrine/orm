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

/**
 * Executes the SQL statements for bulk DQL DELETE statements on classes in
 * Class Table Inheritance (JOINED).
 *
 * @author      Roman Borschel <roman@code-factory.org>
 * @license     http://www.opensource.org/licenses/lgpl-license.php LGPL
 * @link        http://www.doctrine-project.org
 * @since       2.0
 * @version     $Revision$
 */
class MultiTableDeleteExecutor extends AbstractExecutor
{
    private $_createTempTableSql;
    private $_dropTempTableSql;
    private $_insertSql;
    
    /**
     * Initializes a new <tt>MultiTableDeleteExecutor</tt>.
     *
     * @param Node $AST The root AST node of the DQL query.
     * @param SqlWalker $sqlWalker The walker used for SQL generation from the AST.
     */
    public function __construct(\Doctrine\ORM\Query\AST\Node $AST, $sqlWalker)
    {
        $em = $sqlWalker->getEntityManager();
        $conn = $em->getConnection();
        
        $primaryClass = $sqlWalker->getEntityManager()->getClassMetadata(
            $AST->getDeleteClause()->getAbstractSchemaName()
        );
        $rootClass = $em->getClassMetadata($primaryClass->rootEntityName);
        
        $tempTable = $rootClass->getTemporaryIdTableName();
        $idColumnNames = $rootClass->getIdentifierColumnNames();
        $idColumnList = implode(', ', $idColumnNames);

        // 1. Create a INSERT INTO temptable ... VALUES ( SELECT statement where the SELECT statement
        // selects the identifiers and uses the WhereClause of the $AST ).
        $this->_insertSql = 'INSERT INTO ' . $tempTable . ' (' . $idColumnList . ')'
                . ' SELECT ' . $idColumnList . ' FROM ' . $conn->quoteIdentifier($rootClass->primaryTable['name']) . ' t0';
        
        // Append WHERE clause, if there is one.
        if ($AST->getWhereClause()) {
            $sqlWalker->setSqlTableAlias($rootClass->primaryTable['name'] . $AST->getDeleteClause()->getAliasIdentificationVariable(), 't0');
            $this->_insertSql .= $sqlWalker->walkWhereClause($AST->getWhereClause());
        }

        // 2. Create ID subselect statement used in DELETE .... WHERE ... IN (subselect)
        $idSubselect = 'SELECT ' . $idColumnList . ' FROM ' . $tempTable;

        // 3. Create and store DELETE statements
        $classNames = array_merge($primaryClass->parentClasses, array($primaryClass->name), $primaryClass->subClasses);
        foreach (array_reverse($classNames) as $className) {
            $tableName = $em->getClassMetadata($className)->primaryTable['name'];
            $this->_sqlStatements[] = 'DELETE FROM ' . $conn->quoteIdentifier($tableName)
                    . ' WHERE (' . $idColumnList . ') IN (' . $idSubselect . ')';
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
     * @param Doctrine\DBAL\Connection $conn The database connection that is used to execute the queries.
     * @param array $params  The parameters.
     * @override
     */
    public function execute(\Doctrine\DBAL\Connection $conn, array $params)
    {
        $numDeleted = 0;
        
        // Create temporary id table
        $conn->exec($this->_createTempTableSql);
        
        // Insert identifiers
        $conn->exec($this->_insertSql, $params);

        // Execute DELETE statements
        for ($i=0, $count=count($this->_sqlStatements); $i<$count; ++$i) {
            if ($i == $count-1) {
                $numDeleted = $conn->exec($this->_sqlStatements[$i]);
            } else {
                $conn->exec($this->_sqlStatements[$i]);
            }
        }
        
        // Drop temporary table
        $conn->exec($this->_dropTempTableSql);
        
        return $numDeleted;
    }
}