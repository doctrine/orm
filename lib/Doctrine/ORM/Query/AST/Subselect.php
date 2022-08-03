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

namespace Doctrine\ORM\Query\AST;

/**
 * Subselect ::= SimpleSelectClause SubselectFromClause [WhereClause] [GroupByClause] [HavingClause] [OrderByClause]
 *
 * @link    www.doctrine-project.org
 */
class Subselect extends Node
{
    /** @var SimpleSelectClause */
    public $simpleSelectClause;

    /** @var SubselectFromClause */
    public $subselectFromClause;

    /** @var WhereClause|null */
    public $whereClause;

    /** @var GroupByClause|null */
    public $groupByClause;

    /** @var HavingClause|null */
    public $havingClause;

    /** @var OrderByClause|null */
    public $orderByClause;

    /**
     * @param SimpleSelectClause  $simpleSelectClause
     * @param SubselectFromClause $subselectFromClause
     */
    public function __construct($simpleSelectClause, $subselectFromClause)
    {
        $this->simpleSelectClause  = $simpleSelectClause;
        $this->subselectFromClause = $subselectFromClause;
    }

    /**
     * {@inheritdoc}
     */
    public function dispatch($sqlWalker)
    {
        return $sqlWalker->walkSubselect($this);
    }
}
