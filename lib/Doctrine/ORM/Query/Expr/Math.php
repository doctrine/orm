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

namespace Doctrine\ORM\Query\Expr;

/**
 * Expression class for DQL math statements.
 *
 * @link    www.doctrine-project.org
 */
class Math
{
    /** @var mixed */
    protected $leftExpr;

    /** @var string */
    protected $operator;

    /** @var mixed */
    protected $rightExpr;

    /**
     * Creates a mathematical expression with the given arguments.
     *
     * @param mixed  $leftExpr
     * @param string $operator
     * @param mixed  $rightExpr
     */
    public function __construct($leftExpr, $operator, $rightExpr)
    {
        $this->leftExpr  = $leftExpr;
        $this->operator  = $operator;
        $this->rightExpr = $rightExpr;
    }

    /**
     * @return mixed
     */
    public function getLeftExpr()
    {
        return $this->leftExpr;
    }

    /**
     * @return string
     */
    public function getOperator()
    {
        return $this->operator;
    }

    /**
     * @return mixed
     */
    public function getRightExpr()
    {
        return $this->rightExpr;
    }

    /**
     * @return string
     */
    public function __toString()
    {
        // Adjusting Left Expression
        $leftExpr = (string) $this->leftExpr;

        if ($this->leftExpr instanceof Math) {
            $leftExpr = '(' . $leftExpr . ')';
        }

        // Adjusting Right Expression
        $rightExpr = (string) $this->rightExpr;

        if ($this->rightExpr instanceof Math) {
            $rightExpr = '(' . $rightExpr . ')';
        }

        return $leftExpr . ' ' . $this->operator . ' ' . $rightExpr;
    }
}
