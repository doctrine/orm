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
 * and is licensed under the MIT license. For more information, see
 * <http://www.doctrine-project.org>.
 */

namespace Doctrine\ORM\Query\Exec;

use Doctrine\DBAL\Cache\QueryCacheProfile;
use Doctrine\DBAL\Connection;
use Doctrine\DBAL\Driver\ResultStatement;
use Doctrine\DBAL\Types\Type;

/**
 * Base class for SQL statement executors.
 *
 * @link        http://www.doctrine-project.org
 *
 * @todo Rename: AbstractSQLExecutor
 */
abstract class AbstractSqlExecutor
{
    /** @var mixed[]|string */
    protected $_sqlStatements;

    /** @var QueryCacheProfile */
    protected $queryCacheProfile;

    /**
     * Gets the SQL statements that are executed by the executor.
     *
     * @return mixed[]|string  All the SQL update statements.
     */
    public function getSqlStatements()
    {
        return $this->_sqlStatements;
    }

    /**
     * @return void
     */
    public function setQueryCacheProfile(QueryCacheProfile $qcp)
    {
        $this->queryCacheProfile = $qcp;
    }

    /**
     * Do not use query cache
     *
     * @return void
     */
    public function removeQueryCacheProfile()
    {
        $this->queryCacheProfile = null;
    }

    /**
     * Executes all sql statements.
     *
     * @param Connection $conn The database connection that is used to execute the queries.
     * @psalm-param array<int, mixed>|array<string, mixed> $params The parameters.
     * @psalm-param array<int, int|string|Type|null>|
     *              array<string, int|string|Type|null> $types The parameter types.
     *
     * @return ResultStatement|int
     */
    abstract public function execute(Connection $conn, array $params, array $types);
}
