<?php declare(strict_types=1);

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

namespace Doctrine\ORM\Query\Expr;

/**
 * Expression class for DQL join.
 *
 * @link    www.doctrine-project.org
 * @since   2.0
 * @author  Guilherme Blanco <guilhermeblanco@hotmail.com>
 * @author  Jonathan Wage <jonwage@gmail.com>
 * @author  Roman Borschel <roman@code-factory.org>
 */
class Join
{
    const INNER_JOIN    = 'INNER';
    const LEFT_JOIN     = 'LEFT';

    const ON            = 'ON';
    const WITH          = 'WITH';

    /**
     * @var string
     */
    protected $joinType;

    /**
     * @var string
     */
    protected $join;

    /**
     * @var string
     */
    protected $alias;

    /**
     * @var string
     */
    protected $conditionType;

    /**
     * @var string
     */
    protected $condition;

    /**
     * @var string
     */
    protected $indexBy;

    /**
     * @param string      $joinType      The condition type constant. Either INNER_JOIN or LEFT_JOIN.
     * @param string      $join          The relationship to join.
     * @param string|null $alias         The alias of the join.
     * @param string|null $conditionType The condition type constant. Either ON or WITH.
     * @param string|null $condition     The condition for the join.
     * @param string|null $indexBy       The index for the join.
     */
    public function __construct(string $joinType, string $join, ?string $alias = null, ?string $conditionType = null, ?string $condition = null, ?string $indexBy = null)
    {
        $this->joinType       = $joinType;
        $this->join           = $join;
        $this->alias          = $alias;
        $this->conditionType  = $conditionType;
        $this->condition      = $condition;
        $this->indexBy        = $indexBy;
    }

    /**
     * @return string 
     */
    public function getJoinType(): string
    {
        return $this->joinType;
    }

    /**
     * @return string
     */
    public function getJoin(): string
    {
        return $this->join;
    }

    /**
     * @return string
     */
    public function getAlias(): string
    {
        return $this->alias;
    }

    /**
     * @return string
     */
    public function getConditionType(): string
    {
        return $this->conditionType;
    }

    /**
     * @return string
     */
    public function getCondition(): string
    {
        return $this->condition;
    }

    /**
     * @return string
     */
    public function getIndexBy(): string
    {
        return $this->indexBy;
    }

    /**
     * @return string
     */
    public function __toString(): string
    {
        return strtoupper($this->joinType) . ' JOIN ' . $this->join
             . ($this->alias ? ' ' . $this->alias : '')
             . ($this->indexBy ? ' INDEX BY ' . $this->indexBy : '')
             . ($this->condition ? ' ' . strtoupper($this->conditionType) . ' ' . $this->condition : '');
    }
}
