<?php

declare(strict_types=1);

namespace Doctrine\ORM\Query;

use Doctrine\ORM\Exception\ORMException;
use Doctrine\ORM\Mapping\AssociationMapping;
use Doctrine\ORM\Query\AST\PathExpression;
use Exception;
use Stringable;
use Throwable;

class QueryException extends Exception implements ORMException
{
    public static function dqlError(string $dql): self
    {
        return new self($dql);
    }

    public static function syntaxError(string $message, Throwable|null $previous = null): self
    {
        return new self('[Syntax Error] ' . $message, 0, $previous);
    }

    public static function semanticalError(string $message, Throwable|null $previous = null): self
    {
        return new self('[Semantical Error] ' . $message, 0, $previous);
    }

    public static function invalidLockMode(): self
    {
        return new self('Invalid lock mode hint provided.');
    }

    public static function invalidParameterType(string $expected, string $received): self
    {
        return new self('Invalid parameter type, ' . $received . ' given, but ' . $expected . ' expected.');
    }

    public static function invalidParameterPosition(string $pos): self
    {
        return new self('Invalid parameter position: ' . $pos);
    }

    public static function tooManyParameters(int $expected, int $received): self
    {
        return new self('Too many parameters: the query defines ' . $expected . ' parameters and you bound ' . $received);
    }

    public static function tooFewParameters(int $expected, int $received): self
    {
        return new self('Too few parameters: the query defines ' . $expected . ' parameters but you only bound ' . $received);
    }

    public static function invalidParameterFormat(string $value): self
    {
        return new self('Invalid parameter format, ' . $value . ' given, but :<name> or ?<num> expected.');
    }

    public static function unknownParameter(string $key): self
    {
        return new self('Invalid parameter: token ' . $key . ' is not defined in the query.');
    }

    public static function parameterTypeMismatch(): self
    {
        return new self('DQL Query parameter and type numbers mismatch, but have to be exactly equal.');
    }

    public static function invalidPathExpression(PathExpression $pathExpr): self
    {
        return new self(
            "Invalid PathExpression '" . $pathExpr->identificationVariable . '.' . $pathExpr->field . "'.",
        );
    }

    public static function invalidLiteral(string|Stringable $literal): self
    {
        return new self("Invalid literal '" . $literal . "'");
    }

    public static function iterateWithFetchJoinCollectionNotAllowed(AssociationMapping $assoc): self
    {
        return new self(
            'Invalid query operation: Not allowed to iterate over fetch join collections ' .
            'in class ' . $assoc->sourceEntity . ' association ' . $assoc->fieldName,
        );
    }

    public static function partialObjectsAreDangerous(): self
    {
        return new self(
            'Loading partial objects is dangerous. Fetch full objects or consider ' .
            'using a different fetch mode. If you really want partial objects, ' .
            'set the doctrine.forcePartialLoad query hint to TRUE.',
        );
    }

    /**
     * @param string[] $assoc
     * @phpstan-param array<string, string> $assoc
     */
    public static function overwritingJoinConditionsNotYetSupported(array $assoc): self
    {
        return new self(
            'Unsupported query operation: It is not yet possible to overwrite the join ' .
            'conditions in class ' . $assoc['sourceEntityName'] . ' association ' . $assoc['fieldName'] . '. ' .
            'Use WITH to append additional join conditions to the association.',
        );
    }

    public static function associationPathInverseSideNotSupported(PathExpression $pathExpr): self
    {
        return new self(
            'A single-valued association path expression to an inverse side is not supported in DQL queries. ' .
            'Instead of "' . $pathExpr->identificationVariable . '.' . $pathExpr->field . '" use an explicit join.',
        );
    }

    public static function iterateWithFetchJoinNotAllowed(AssociationMapping $assoc): self
    {
        return new self(
            'Iterate with fetch join in class ' . $assoc->sourceEntity .
            ' using association ' . $assoc->fieldName . ' not allowed.',
        );
    }

    public static function eagerFetchJoinWithNotAllowed(string $sourceEntity, string $fieldName): self
    {
        return new self(
            'Associations with fetch-mode=EAGER may not be using WITH conditions in
             "' . $sourceEntity . '#' . $fieldName . '".',
        );
    }

    public static function iterateWithMixedResultNotAllowed(): self
    {
        return new self('Iterating a query with mixed results (using scalars) is not supported.');
    }

    public static function associationPathCompositeKeyNotSupported(): self
    {
        return new self(
            'A single-valued association path expression to an entity with a composite primary ' .
            'key is not supported. Explicitly name the components of the composite primary key ' .
            'in the query.',
        );
    }

    public static function instanceOfUnrelatedClass(string $className, string $rootClass): self
    {
        return new self("Cannot check if a child of '" . $rootClass . "' is instanceof '" . $className . "', " .
            'inheritance hierarchy does not exists between these two classes.');
    }

    public static function invalidQueryComponent(string $dqlAlias): self
    {
        return new self(
            "Invalid query component given for DQL alias '" . $dqlAlias . "', " .
            "requires 'metadata', 'parent', 'relation', 'map', 'nestingLevel' and 'token' keys.",
        );
    }
}
