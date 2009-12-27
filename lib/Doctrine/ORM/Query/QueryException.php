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
 */
class QueryException extends \Doctrine\Common\DoctrineException 
{
    public static function syntaxError($message)
    {
        return new self('[Syntax Error] ' . $message);
    }
    
    public static function semanticalError($message)
    {
        return new self('[Semantical Error] ' . $message);
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
    
    public static function invalidPathExpression($pathExpr)
    {
        return new self(
            "Invalid PathExpression '" . $pathExpr->identificationVariable . 
            "." . implode('.', $pathExpr->parts) . "'."
        );
    }

    /**
     * @param Doctrine\ORM\Mapping\AssociationMapping $assoc
     */
    public static function iterateWithFetchJoinCollectionNotAllowed($assoc)
    {
        return new self(
            "Invalid query operation: Not allowed to iterate over fetch join collections ".
            "in class ".$assoc->sourceEntityName." assocation ".$assoc->sourceFieldName
        );
    }
}