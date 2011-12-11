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

namespace Doctrine\ORM\Query;

use Doctrine\ORM\Query\AST\PathExpression;

/**
 * Description of QueryException
 *
 * @license http://www.opensource.org/licenses/lgpl-license.php LGPL
 * @link    www.doctrine-project.org
 * @since   2.0
 * @version $Revision: 3938 $
 * @author  Guilherme Blanco <guilhermeblanco@hotmail.com>
 * @author  Jonathan Wage <jonwage@gmail.com>
 * @author  Roman Borschel <roman@code-factory.org>
 * @author  Benjamin Eberlei <kontakt@beberlei.de>
 */
class QueryException extends \Doctrine\ORM\ORMException
{
    public static function syntaxError($message)
    {
        return new self('[Syntax Error] ' . $message);
    }

    public static function semanticalError($message)
    {
        return new self('[Semantical Error] ' . $message);
    }

    public static function invalidParameterType($expected, $received)
    {
        return new self('Invalid parameter type, ' . $received . ' given, but ' . $expected . ' expected.');
    }

    public static function invalidParameterPosition($pos)
    {
        return new self('Invalid parameter position: ' . $pos);
    }

    public static function invalidParameterNumber()
    {
        return new self("Invalid parameter number: number of bound variables does not match number of tokens");
    }

    public static function invalidParameterFormat($value)
    {
        return new self('Invalid parameter format, '.$value.' given, but :<name> or ?<num> expected.');
    }

    public static function unknownParameter($key)
    {
        return new self("Invalid parameter: token ".$key." is not defined in the query.");
    }

    public static function parameterTypeMissmatch()
    {
        return new self("DQL Query parameter and type numbers missmatch, but have to be exactly equal.");
    }

    public static function invalidPathExpression($pathExpr)
    {
        return new self(
            "Invalid PathExpression '" . $pathExpr->identificationVariable . "." . $pathExpr->field . "'."
        );
    }

    public static function invalidLiteral($literal) {
        return new self("Invalid literal '$literal'");
    }

    /**
     * @param \Doctrine\ORM\Mapping\AssociationMapping $assoc
     */
    public static function iterateWithFetchJoinCollectionNotAllowed($assoc)
    {
        return new self(
            "Invalid query operation: Not allowed to iterate over fetch join collections ".
            "in class ".$assoc['sourceEntity']." assocation ".$assoc['fieldName']
        );
    }

    public static function partialObjectsAreDangerous()
    {
        return new self(
            "Loading partial objects is dangerous. Fetch full objects or consider " .
            "using a different fetch mode. If you really want partial objects, " .
            "set the doctrine.forcePartialLoad query hint to TRUE."
        );
    }

    public static function overwritingJoinConditionsNotYetSupported($assoc)
    {
        return new self(
            "Unsupported query operation: It is not yet possible to overwrite the join ".
            "conditions in class ".$assoc['sourceEntityName']." assocation ".$assoc['fieldName'].". ".
            "Use WITH to append additional join conditions to the association."
        );
    }

    public static function associationPathInverseSideNotSupported()
    {
        return new self(
            "A single-valued association path expression to an inverse side is not supported".
            " in DQL queries. Use an explicit join instead."
        );
    }

    public static function iterateWithFetchJoinNotAllowed($assoc) {
        return new self(
            "Iterate with fetch join in class " . $assoc['sourceEntity'] .
            " using association " . $assoc['fieldName'] . " not allowed."
        );
    }

    public static function associationPathCompositeKeyNotSupported()
    {
        return new self(
            "A single-valued association path expression to an entity with a composite primary ".
            "key is not supported. Explicitly name the components of the composite primary key ".
            "in the query."
        );
    }

    public static function instanceOfUnrelatedClass($className, $rootClass)
    {
        return new self("Cannot check if a child of '" . $rootClass . "' is instanceof '" . $className . "', " .
                "inheritance hierachy exists between these two classes.");
    }
}