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

namespace Doctrine\ORM\Persisters;

use Doctrine\Common\Collections\Expr\ExpressionVisitor;
use Doctrine\Common\Collections\Expr\Comparison;
use Doctrine\Common\Collections\Expr\Value;
use Doctrine\Common\Collections\Expr\CompositeExpression;

use Doctrine\ORM\Mapping\ClassMetadata;

use Doctrine\DBAL\Types\Type;
use Doctrine\DBAL\Connection;

/**
 * Extract the values from a criteria/expression
 *
 * @author Benjamin Eberlei <kontakt@beberlei.de>
 */
class SqlValueVisitor extends ExpressionVisitor
{
    /**
     * @var array
     */
    private $values = array();

    /**
     * @var array
     */
    private $types  = array();

    /**
     * Convert a comparison expression into the target query language output
     *
     * @param \Doctrine\Common\Collections\Expr\Comparison $comparison
     *
     * @return mixed
     */
    public function walkComparison(Comparison $comparison)
    {
        $value          = $comparison->getValue()->getValue();
        $field          = $comparison->getField();
        
        $this->values[] = $value;
        $this->types[]  = array($field, $value);
    }

    /**
     * Convert a composite expression into the target query language output
     *
     * @param \Doctrine\Common\Collections\Expr\CompositeExpression $expr
     *
     * @return mixed
     */
    public function walkCompositeExpression(CompositeExpression $expr)
    {
        foreach ($expr->getExpressionList() as $child) {
            $this->dispatch($child);
        }
    }

    /**
     * Convert a value expression into the target query language part.
     *
     * @param \Doctrine\Common\Collections\Expr\Value $value
     *
     * @return mixed
     */
    public function walkValue(Value $value)
    {
        return;
    }

    /**
     * Return the Parameters and Types necessary for matching the last visited expression.
     *
     * @return array
     */
    public function getParamsAndTypes()
    {
        return array($this->values, $this->types);
    }
}
