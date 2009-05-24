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
 * @todo For a good implementation that uses temporary tables see the Hibernate sources:
 *       (org.hibernate.hql.ast.exec.MultiTableDeleteExecutor).
 */
class MultiTableDeleteExecutor extends AbstractExecutor
{
    /**
     * Enter description here...
     *
     * @param Node $AST
     */
    public function __construct($AST)
    {
        // 1. Create a INSERT INTO temptable ... VALUES ( SELECT statement where the SELECT statement
        // selects the identifiers and uses the WhereClause of the $AST ).

        // 2. Create ID subselect statement used in DELETE .... WHERE ... IN (subselect)

        // 3. Create and store DELETE statements
        /*$subselect = 'SELECT id1, id2 FROM temptable';
        foreach ($tableNames as $tableName) {
            $this->_sqlStatements[] = 'DELETE FROM ' . $tableName . ' WHERE (id1, id2) IN (subselect)';
        }*/
        // in $this->_sqlStatements
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
        // 1. Create temporary id table if necessary

        //...
    }   
}