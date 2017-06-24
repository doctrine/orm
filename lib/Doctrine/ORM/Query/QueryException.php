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

namespace Doctrine\ORM\Query;

use Doctrine\ORM\Query\AST\PathExpression;

/**
 * Description of QueryException.
 *
 * @link    www.doctrine-project.org
 * @since   2.0
 * @author  Guilherme Blanco <guilhermeblanco@hotmail.com>
 * @author  Jonathan Wage <jonwage@gmail.com>
 * @author  Roman Borschel <roman@code-factory.org>
 * @author  Benjamin Eberlei <kontakt@beberlei.de>
 */
class QueryException extends \Doctrine\ORM\ORMException
{
    /**
     * @param string $dql
     *
     * @return QueryException
     */
    public static function dqlError(string $dql): QueryException
    {
        return new self($dql);
    }

    /**
     * @param string          $message
     * @param \Exception|null $previous
     *
     * @return QueryException
     */
    public static function syntaxError(string $message, ?\Exception $previous = null): QueryException
    {
        return new self('[Syntax Error] ' . $message, 0, $previous);
    }

    /**
     * @param string          $message
     * @param \Exception|null $previous
     *
     * @return QueryException
     */
    public static function semanticalError(string $message, ?\Exception $previous = null): QueryException
    {
        return new self('[Semantical Error] ' . $message, 0, $previous);
    }

    /**
     * @return QueryException
     */
    public static function invalidLockMode(): QueryException
    {
        return new self('Invalid lock mode hint provided.');
    }

    /**
     * @param string $expected
     * @param string $received
     *
     * @return QueryException
     */
    public static function invalidParameterType(string $expected, string $received): QueryException
    {
        return new self('Invalid parameter type, ' . $received . ' given, but ' . $expected . ' expected.');
    }

    /**
     * @param string $pos
     *
     * @return QueryException
     */
    public static function invalidParameterPosition(string $pos): QueryException
    {
        return new self('Invalid parameter position: ' . $pos);
    }

    /**
     * @param integer $expected
     * @param integer $received
     *
     * @return QueryException
     */
    public static function tooManyParameters(int $expected, int $received): QueryException
    {
        return new self('Too many parameters: the query defines ' . $expected . ' parameters and you bound ' . $received);
    }

    /**
     * @param integer $expected
     * @param integer $received
     *
     * @return QueryException
     */
    public static function tooFewParameters(int $expected, int $received): QueryException
    {
        return new self('Too few parameters: the query defines ' . $expected . ' parameters but you only bound ' . $received);
    }

    /**
     * @param string $value
     *
     * @return QueryException
     */
    public static function invalidParameterFormat(string $value): QueryException
    {
        return new self('Invalid parameter format, '.$value.' given, but :<name> or ?<num> expected.');
    }

    /**
     * @param string $key
     *
     * @return QueryException
     */
    public static function unknownParameter(string $key): QueryException
    {
        return new self("Invalid parameter: token ".$key." is not defined in the query.");
    }

    /**
     * @return QueryException
     */
    public static function parameterTypeMismatch(): QueryException
    {
        return new self("DQL Query parameter and type numbers mismatch, but have to be exactly equal.");
    }

    /**
     * @param object $pathExpr
     *
     * @return QueryException
     */
    public static function invalidPathExpression($pathExpr): QueryException
    {
        return new self(
            "Invalid PathExpression '" . $pathExpr->identificationVariable . "." . $pathExpr->field . "'."
        );
    }

    /**
     * @param string $literal
     *
     * @return QueryException
     */
    public static function invalidLiteral(string $literal): QueryException
    {
        return new self("Invalid literal '$literal'");
    }

    /**
     * @param array $assoc
     *
     * @return QueryException
     */
    public static function iterateWithFetchJoinCollectionNotAllowed(array $assoc): QueryException
    {
        return new self(
            "Invalid query operation: Not allowed to iterate over fetch join collections ".
            "in class ".$assoc['sourceEntity']." association ".$assoc['fieldName']
        );
    }

    /**
     * @return QueryException
     */
    public static function partialObjectsAreDangerous(): QueryException
    {
        return new self(
            "Loading partial objects is dangerous. Fetch full objects or consider " .
            "using a different fetch mode. If you really want partial objects, " .
            "set the doctrine.forcePartialLoad query hint to TRUE."
        );
    }

    /**
     * @param array $assoc
     *
     * @return QueryException
     */
    public static function overwritingJoinConditionsNotYetSupported(array $assoc): QueryException
    {
        return new self(
            "Unsupported query operation: It is not yet possible to overwrite the join ".
            "conditions in class ".$assoc['sourceEntityName']." association ".$assoc['fieldName'].". ".
            "Use WITH to append additional join conditions to the association."
        );
    }

    /**
     * @param PathExpression $pathExpr
     *
     * @return QueryException
     */
    public static function associationPathInverseSideNotSupported(PathExpression $pathExpr): QueryException
    {
        return new self(
            'A single-valued association path expression to an inverse side is not supported in DQL queries. ' .
            'Instead of "' . $pathExpr->identificationVariable . '.' . $pathExpr->field . '" use an explicit join.'
        );
    }

    /**
     * @param array $assoc
     *
     * @return QueryException
     */
    public static function iterateWithFetchJoinNotAllowed(array $assoc): QueryException
    {
        return new self(
            "Iterate with fetch join in class " . $assoc['sourceEntity'] .
            " using association " . $assoc['fieldName'] . " not allowed."
        );
    }

    /**
     * @return QueryException
     */
    public static function associationPathCompositeKeyNotSupported(): QueryException
    {
        return new self(
            "A single-valued association path expression to an entity with a composite primary ".
            "key is not supported. Explicitly name the components of the composite primary key ".
            "in the query."
        );
    }

    /**
     * @param string $className
     * @param string $rootClass
     *
     * @return QueryException
     */
    public static function instanceOfUnrelatedClass(string $className, string $rootClass): QueryException
    {
        return new self("Cannot check if a child of '" . $rootClass . "' is instanceof '" . $className . "', " .
            "inheritance hierarchy does not exists between these two classes.");
    }

    /**
     * @param string $dqlAlias
     *
     * @return QueryException
     */
    public static function invalidQueryComponent(string $dqlAlias): QueryException
    {
        return new self(
            "Invalid query component given for DQL alias '" . $dqlAlias . "', ".
            "requires 'metadata', 'parent', 'relation', 'map', 'nestingLevel' and 'token' keys."
        );
    }
}
