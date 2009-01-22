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
 * <http://www.phpdoctrine.org>.
 */

namespace Doctrine\ORM\Query\Exec;

/**
 * Doctrine_ORM_Query_QueryResult
 *
 * @author      Roman Borschel <roman@code-factory.org>
 * @license     http://www.opensource.org/licenses/lgpl-license.php LGPL
 * @link        http://www.phpdoctrine.org
 * @since       2.0
 * @version     $Revision$
 */
abstract class AbstractExecutor implements \Serializable
{
    protected $_sqlStatements;

    public function __construct(\Doctrine\ORM\Query\AST\Node $AST, $sqlWalker)
    {
    }

    /**
     * Gets the SQL statements that are executed by the executor.
     *
     * @return array  All the SQL update statements.
     */
    public function getSqlStatements()
    {
        return $this->_sqlStatements;
    }

    /**
     * Executes all sql statements.
     *
     * @param Doctrine_Connection $conn  The database connection that is used to execute the queries.
     * @param array $params  The parameters.
     */
    abstract public function execute(\Doctrine\DBAL\Connection $conn, array $params);

    /**
     * Factory method.
     * Creates an appropriate sql executor for the given AST.
     *
     * @param Doctrine_ORM_Query_AST $AST  The root node of the AST.
     * @return Doctrine_ORM_Query_SqlExecutor_Abstract  The executor that is suitable for the given AST.
     */
    public static function create(\Doctrine\ORM\Query\AST\Node $AST, $sqlWalker)
    {
        $isDeleteStatement = $AST instanceof \Doctrine\ORM\Query\AST\DeleteStatement;
        $isUpdateStatement = $AST instanceof \Doctrine\ORM\Query\AST\UpdateStatement;

        if ($isUpdateStatement || $isDeleteStatement) {
            // TODO: Inspect the $AST and create the proper executor like so (pseudo-code):
            /*
            if (primaryClassInFromClause->isMultiTable()) {
                   if ($isDeleteStatement) {
                       return new Doctrine_ORM_Query_SqlExecutor_MultiTableDelete($AST);
                   } else {
                       return new Doctrine_ORM_Query_SqlExecutor_MultiTableUpdate($AST);
                   }
            } else ...
            */
            return new SingleTableDeleteUpdateExecutor($AST);
        } else {
            return new SingleSelectExecutor($AST, $sqlWalker);
        }
    }


    /**
     * Serializes the sql statements of the executor.
     *
     * @return string
     */
    public function serialize()
    {
        return serialize($this->_sqlStatements);
    }


    /**
     * Reconstructs the executor with it's sql statements.
     */
    public function unserialize($serialized)
    {
        $this->_sqlStatements = unserialize($serialized);
    }
}