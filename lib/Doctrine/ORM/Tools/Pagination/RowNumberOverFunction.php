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

namespace Doctrine\ORM\Tools\Pagination;

use Doctrine\ORM\ORMException;
use Doctrine\ORM\Query\AST\Functions\FunctionNode;
use Doctrine\ORM\Query\AST\OrderByClause;
use Doctrine\ORM\Query\Parser;
use Doctrine\ORM\Query\SqlWalker;

use function trim;

/**
 * RowNumberOverFunction
 *
 * Provides ROW_NUMBER() OVER(ORDER BY...) construct for use in LimitSubqueryOutputWalker
 */
class RowNumberOverFunction extends FunctionNode
{
    /** @var OrderByClause */
    public $orderByClause;

    /**
     * @override
     * @inheritdoc
     */
    public function getSql(SqlWalker $sqlWalker)
    {
        return 'ROW_NUMBER() OVER(' . trim($sqlWalker->walkOrderByClause(
            $this->orderByClause
        )) . ')';
    }

    /**
     * @throws ORMException
     *
     * @override
     * @inheritdoc
     */
    public function parse(Parser $parser)
    {
        throw new ORMException('The RowNumberOverFunction is not intended for, nor is it enabled for use in DQL.');
    }
}
