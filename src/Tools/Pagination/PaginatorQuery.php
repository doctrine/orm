<?php

declare(strict_types=1);

namespace Doctrine\ORM\Tools\Pagination;

use Doctrine\Common\Collections\ArrayCollection;
use Doctrine\ORM\EntityManagerInterface;
use Doctrine\ORM\Mapping\ClassMetadata;
use Doctrine\ORM\Query;
use Doctrine\ORM\Query\AST\Join;
use Doctrine\ORM\Query\AST\JoinAssociationDeclaration;
use Doctrine\ORM\Query\AST\Node;
use Doctrine\ORM\Query\AST\SelectExpression;
use Doctrine\ORM\Query\Parameter;
use Doctrine\ORM\Query\Parser;
use Doctrine\ORM\Query\ResultSetMapping;
use Doctrine\ORM\Query\TreeWalker;
use Doctrine\ORM\QueryBuilder;
use Doctrine\ORM\Tools\Pagination\ValueObject\LazyEntityAliasesToClassMetadataMap;
use Doctrine\ORM\Tools\Pagination\ValueObject\SelectFieldIdentificationVariablesToSelectExpressionsMap;
use ReflectionClass;
use ReflectionProperty;
use RuntimeException;

use function array_intersect;
use function array_key_exists;
use function array_keys;
use function array_map;
use function array_unique;
use function assert;
use function count;
use function in_array;
use function is_array;
use function is_string;
use function reset;

/**
 * Provides the implementation shared by {@see Paginator} and {@see CursorPaginator}.
 *
 * @internal
 *
 * @template-covariant TEntityClass
 */
trait PaginatorQuery
{
    private int|null $count = null;
    // Whether to use LimitSubqueryWalker or LimitSubqueryOutputWalker. Null means "auto-decide".
    private bool|null $useResultQueryOutputWalker = null;
    // Whether to use CountWalker or CountOutputWalker. Null means "auto-decide".
    private bool|null $useCountQueryOutputWalker = null;

    private bool $defaultCountWalkerNeedsToUseCountDistinct = true;
    private bool $countWalkerShouldDropGroupByClause        = false;

    /**
     * Create an instance of Paginator with auto-detection of whether the provided
     * query is suitable for simple (and fast) pagination queries, or whether a complex
     * set of pagination queries has to be used.
     *
     * @return static<TEntityClass>
     */
    public static function newWithAutoDetection(Query|QueryBuilder $query): static
    {
        if ($query instanceof QueryBuilder) {
            $query = $query->getQuery();
        }

        $queryAST = $query->getAST();
        assert($queryAST instanceof Query\AST\SelectStatement);

        [
            'queryIsPlainSelectScalars' => $queryIsPlainSelectScalars,
            'queryUsesGrouping' => $queryUsesGrouping,
            'queryGroupingCouldUseColumnsFromToManyJoins' => $queryGroupingCouldUseColumnsFromToManyJoins,
            'queryGroupingProducesGroupsCountEqualToRootEntityRecordsCount' => $queryGroupingProducesGroupsCountEqualToRootEntityRecordsCount,
            'hasHavingClause' => $queryHasHavingClause,
            'rootEntityHasSingleIdentifierFieldName' => $rootEntityHasSingleIdentifierFieldName,
            'couldProduceDuplicates' => $queryCouldProduceDuplicates,
            'couldHaveToManyJoins' => $queryCouldHaveToManyJoins,
            'resultQueryCanUseLimitSubqueryWalker' => $resultQueryCanUseLimitSubqueryWalker,
        ] = self::autoDetectQueryFeatures($query->getEntityManager(), $queryAST);

        /** @var static<TEntityClass> $paginator */
        $paginator = static::doCreateNewWithAutoDetection(
            $query,
            // A "plain select scalars" query never runs into the duplication problems that the Paginator tries to handle.
            // In that case, the result from the Paginator should be exactly what the query Selects, even if it does
            // end up in duplicated scalars (in which case, the user should use SELECT DISTINCT on their own).
            $queryIsPlainSelectScalars ? false : $queryCouldProduceDuplicates,
        );

        // Case when the query already has a custom output walker: do not use any of the further "auto detected" result.
        // Note that this will force the paginator to use the equivalent non-output tree walkers which are more than
        // likely to not produce correct results (or they could even not run at all), so custom output walkers are not
        // exactly a very supported use case in the Paginator.
        if ($query->getHint(Query::HINT_CUSTOM_OUTPUT_WALKER) !== false) {
            return $paginator;
        }

        // Conditions for when the CountWalker doesn't need to use COUNT(DISTINCT), assuming CountWalker ends up
        // being used (to be decided by ::useCountQueryOutputWalker).
        if ($queryIsPlainSelectScalars || ! $queryCouldHaveToManyJoins) {
            $paginator->defaultCountWalkerNeedsToUseCountDistinct = false;
        }

        if ($resultQueryCanUseLimitSubqueryWalker) {
            $paginator->useResultQueryOutputWalker = false;
        }

        // The following is ensuring the conditions for when the CountWalker cannot be used (i.e. when the
        // CountOutputWalker has to be used). Note how CountWalker _is_ compatible with _some_ queries with ToMany joins,
        // as long as COUNT(DISTINCT) is also used (which is what ::getCountQuery() ensures).
        //
        // HAVING exists -> cannot use CountWalker (it throws an exception)
        $paginator->useCountQueryOutputWalker = $queryHasHavingClause !== false
            // The root entity does not have a single identifier and CountWalker has to use SELECT COUNT(DISTINCT id)
            // -> cannot use CountWalker because it does not support root entities with composite primary key for that
            // kind of query
            || (
                $rootEntityHasSingleIdentifierFieldName !== true
                && ($query->hasHint(CountWalker::HINT_DISTINCT) || $paginator->defaultCountWalkerNeedsToUseCountDistinct)
            )
            // It wasn't determined whether the query uses grouping -> cannot use CountWalker
            || $queryUsesGrouping === null
            // The query uses grouping with columns from the ToMany joins -> cannot use CountWalker, because the query
            // produces "duplicate rows" that cannot be "deduplicated" with COUNT(DISTINCT). Note that it does mean
            // that queries with ToMany joins and without grouping can unconditionally use the CountWalker (but with
            // COUNT(DISTINCT)).
            || ($queryUsesGrouping === true && $queryGroupingCouldUseColumnsFromToManyJoins)
            // The query uses grouping that does not produce the same number of groups as the count of all root entity records
            // -> cannot use CountWalker, because it would produce an incorrect result. Think what happens when grouping
            // on a non-unique, nullable column.
            || ($queryUsesGrouping === true && ! $queryGroupingProducesGroupsCountEqualToRootEntityRecordsCount);

        // If CountWalker can be used, then tell it whether it should drop the GroupBy clause because the count of
        // the GroupBy result is the same as the count of all root entity records.
        if (! $paginator->useCountQueryOutputWalker && $queryAST->groupByClause !== null) {
            $paginator->countWalkerShouldDropGroupByClause = $queryGroupingProducesGroupsCountEqualToRootEntityRecordsCount;
        }

        return $paginator;
    }

    abstract protected static function doCreateNewWithAutoDetection(Query $query, bool $queryProducesDuplicates): self;

    /**
     * @return array{
     *  queryIsPlainSelectScalars: bool|null,
     *  hasMultipleFrom: bool|null,
     *  queryUsesGrouping: bool|null,
     *  queryGroupingCouldUseColumnsFromToManyJoins: bool,
     *  queryGroupingProducesGroupsCountEqualToRootEntityRecordsCount: bool,
     *  hasHavingClause: bool|null,
     *  rootEntityHasSingleIdentifierFieldName: bool|null,
     *  couldProduceDuplicates: bool,
     *  couldHaveToManyJoins: bool,
     *  resultQueryCanUseLimitSubqueryWalker: bool|null,
     * }
     */
    private static function autoDetectQueryFeatures(EntityManagerInterface $entityManager, Query\AST\SelectStatement $queryAST): array
    {
        $queryFeatures = [
            // Null means undetermined
            'queryIsPlainSelectScalars' => null,
            'hasMultipleFrom' => null,
            'queryUsesGrouping' => null,
            // Note: the "queryGrouping*" values are only valid if "queryUsesGrouping" is not null
            'queryGroupingCouldUseColumnsFromToManyJoins' => false,
            'queryGroupingProducesGroupsCountEqualToRootEntityRecordsCount' => false,
            'hasHavingClause' => null,
            'rootEntityHasSingleIdentifierFieldName' => null,
            'couldProduceDuplicates' => true,
            'couldHaveToManyJoins' => true,
            'resultQueryCanUseLimitSubqueryWalker' => null,
        ];

        // First, exclude the special case when the query could be selecting only aggregate functions (i.e. always
        // producing 1 row). This exclusion cannot actually be done "safely" (because aggregate functions could be buried
        // within other functions to an arbitrary level), therefore instead of trying to detect this case with 100%
        // certainty, only see whether it could potentially occur. I.e. if none of the Select expressions are a plain
        // string or a plain PathExpression, then it could potentially be this "aggregate functions only" case. Do not
        // support queries of this kind (even if they end up being falsly detected to be of this kind).
        $entityAliasOrPathExpressionPresentInSelectClause = false;
        $anyEntityAliasPresentInSelectClause              = false;

        foreach ($queryAST->selectClause->selectExpressions as $selectExpression) {
            if (! $selectExpression instanceof SelectExpression) {
                return $queryFeatures;
            }

            if (is_string($selectExpression->expression)) {
                $anyEntityAliasPresentInSelectClause              = true;
                $entityAliasOrPathExpressionPresentInSelectClause = true;

                break;
            }

            if ($selectExpression->expression instanceof Query\AST\PathExpression) {
                $entityAliasOrPathExpressionPresentInSelectClause = true;

                continue;
            }
        }

        if (! $entityAliasOrPathExpressionPresentInSelectClause) {
            return $queryFeatures;
        }

        $queryFeatures['queryUsesGrouping'] = $queryAST->selectClause->isDistinct || $queryAST->groupByClause !== null;
        $queryFeatures['hasHavingClause']   = $queryAST->havingClause !== null;

        // When the query uses multiple entities in the FROM clause (also known as CROSS JOIN), then this
        // query's result cannot in fact be counted by the Paginator. Don't attempt to auto-detect anything here and
        // let the further Paginator code throw exceptions in case the user tries to count the result.
        $from                             = $queryAST->fromClause->identificationVariableDeclarations;
        $queryFeatures['hasMultipleFrom'] = count($from) > 1;
        if ($queryFeatures['hasMultipleFrom']) {
            return $queryFeatures;
        }

        $fromRoot = reset($from);
        if (! $fromRoot instanceof Query\AST\IdentificationVariableDeclaration) {
            return $queryFeatures;
        }

        if (! $fromRoot->rangeVariableDeclaration) {
            return $queryFeatures;
        }

        $rootAlias                                               = $fromRoot->rangeVariableDeclaration->aliasIdentificationVariable;
        $rootClassMetadata                                       = $entityManager->getClassMetadata($fromRoot->rangeVariableDeclaration->abstractSchemaName);
        $queryFeatures['rootEntityHasSingleIdentifierFieldName'] = $rootClassMetadata->hasSingleIdentifier();

        $lazyEntityAliasesToClassMetadataMap = new LazyEntityAliasesToClassMetadataMap($entityManager);
        $lazyEntityAliasesToClassMetadataMap->addEntityAlias($rootAlias, $rootClassMetadata);
        /** @var string[] $toManyJoinsAliases */
        $toManyJoinsAliases = [];

        // Check the Joins list.
        foreach ($fromRoot->joins as $join) {
            if (! $join instanceof Join || ! $join->joinAssociationDeclaration instanceof JoinAssociationDeclaration) {
                return $queryFeatures;
            }

            $joinParentAlias     = $join->joinAssociationDeclaration->joinAssociationPathExpression->identificationVariable;
            $joinParentFieldName = $join->joinAssociationDeclaration->joinAssociationPathExpression->associationField;
            $joinAlias           = $join->joinAssociationDeclaration->aliasIdentificationVariable;

            // Every Join descending from a ToMany Join is "in principle" also a ToMany Join
            if (in_array($joinParentAlias, $toManyJoinsAliases, true)) {
                $toManyJoinsAliases[] = $joinAlias;

                continue;
            }

            $parentClassMetadata = $lazyEntityAliasesToClassMetadataMap->getClassMetadata($joinParentAlias);

            $parentJoinAssociationMapping = $parentClassMetadata->associationMappings[$joinParentFieldName] ?? null;
            if (! $parentJoinAssociationMapping) {
                return $queryFeatures;
            }

            $lazyEntityAliasesToClassMetadataMap->addEntityAlias($joinAlias, $parentJoinAssociationMapping['targetEntity']);

            if (! ($parentJoinAssociationMapping['type'] & ClassMetadata::TO_MANY)) {
                continue;
            }

            // The Join is a ToMany Join.
            $toManyJoinsAliases[] = $joinAlias;
        }

        $queryFeatures['couldHaveToManyJoins'] = count($toManyJoinsAliases) > 0;

        // Check if the query could end up producing "duplicate rows".

        // Check the grouping in the query. Note: "grouping" means that the query either has a GroupBy clause or
        // that it uses "SELECT DISTINCT". Both cases require the same level of "inspection".
        if ($queryFeatures['queryUsesGrouping']) {
            $queryFeatures['queryGroupingCouldUseColumnsFromToManyJoins'] = $queryFeatures['couldHaveToManyJoins'];

            /** @var array<int, string|Query\AST\PathExpression|Node> $groupingNodes */
            $groupingNodes = [];

            // "Collapse" the "SELECT DISTINCT" case into a "GROUP BY [all selected columns]" case. Note: when both
            // "SELECT DISTINCT" and "GROUP BY" are present, then either the "GROUP BY" effectively takes precedence
            // or the entire query is incorrect and will not run. For that reason, when "both are present", "GROUP BY"
            // wins.
            $groupingUsesSelectDistinct = false;
            if ($queryAST->groupByClause !== null) {
                // Note: in the case of GroupBy, the grouping nodes can only be string or Query\AST\PathExpression.
                // I.e. they will never be Node instances (i.e. "expressions").
                $groupingNodes = $queryAST->groupByClause->groupByItems;
            } else {
                $groupingUsesSelectDistinct = true;
                $groupingNodes              = array_map(
                    static fn (SelectExpression $selectExpression): mixed => $selectExpression->expression,
                    $queryAST->selectClause->selectExpressions,
                );
            }

            // Collect information about what is being grouped by.

            /**
             * @var array<string, Query\AST\PathExpression[]> $groupingEntityAliasesToPathExpressions Empty array as value
             * means: GroupBy the entire entity.
             */
            $groupingEntityAliasesToPathExpressions                   = [];
            $selectFieldIdentificationVariablesToSelectExpressionsMap = null;

            foreach ($groupingNodes as $groupingNode) {
                if (is_string($groupingNode)) {
                    // Case when this is an entity alias: store and continue.
                    if ($lazyEntityAliasesToClassMetadataMap->hasEntityAlias($groupingNode)) {
                        $groupingEntityAliasesToPathExpressions[$groupingNode] = [];

                        continue;
                    }

                    // Case when this is a "SELECT DISTINCT", the string value is unrecognised. This should never
                    // happen at this point because the query would have failed AST parsing a lot earlier.
                    if ($groupingUsesSelectDistinct) {
                        throw new RuntimeException('SELECT clause uses an unrecognized alias: ' . $groupingNode);
                    }

                    // Populate a helper map
                    if (! $selectFieldIdentificationVariablesToSelectExpressionsMap) {
                        $selectFieldIdentificationVariablesToSelectExpressionsMap = new SelectFieldIdentificationVariablesToSelectExpressionsMap($queryAST);
                    }

                    // Case when this is an alias from the Select list: see what the Select item is.
                    /** @var Query\AST\PathExpression|mixed|null $usedSelectExpressionExpression */
                    $usedSelectExpressionExpression = $selectFieldIdentificationVariablesToSelectExpressionsMap->getSelectExpression($groupingNode)->expression ?? null;
                    if (! $usedSelectExpressionExpression) {
                        // The GroupBy uses an alias that is unrecognized. This should never happen (because it would be
                        // a SQL/DQL syntax error).
                        throw new RuntimeException('GroupBy clause uses an unrecognized alias: ' . $groupingNode);
                    }

                    // Treat the "extracted" Select expression as the groupingNode and let the rest of the foreach code
                    // handle it.
                    $groupingNode = $usedSelectExpressionExpression;
                }

                if ($groupingNode instanceof Query\AST\PathExpression) {
                    $groupingEntityAliasesToPathExpressions[$groupingNode->identificationVariable][]
                        = $groupingNode;

                    continue;
                }

                // At this point, the grouping node could either be an expression or a literal (both of which: instances
                // of an AST Node). Literals don't change anything, so they are ignored. The expressions however are
                // the tricky part. There's no point trying to inspect the expression totally because it would be a fool's
                // errand (think about complex expressions within expressions). Instead, the following is done: if the
                // expression uses at least 1 column from a ToMany Join, then it _could_ result in duplicate rows, and
                // therefore there is nothing more to inspect. If that's not the case, then the "auto detection" will
                // continue, but it will treat the expression as a "black/Pandora box". In that case, the expression
                // could be complex or could be trivial, but the "auto detection" will not even attempt "to find out".
                //
                // An example of a ToMany usage that does NOT result in duplicate rows, but the "auto detection" will not
                // ask further questions and "assume the worst": "CASE WHEN TRUE THEN 'lorem ipsum' ELSE to_many.id END".

                // Look for ToMany usages only when there actually are ToMany Joins.
                if (! $queryFeatures['couldHaveToManyJoins']) {
                    continue;
                }

                $searchResult = self::searchEntityAliasesUsageInASTNode($toManyJoinsAliases, $groupingNode);

                // Case when the expression is too complex: It's not safe to make any assumptions about what is being
                // grouped on. Return immediately.
                if ($searchResult === null) {
                    return $queryFeatures;
                }

                // Case when there is at least one ToMany column usage: return immediately.
                if ($searchResult === true) {
                    $queryFeatures['resultQueryCanUseLimitSubqueryWalker'] = self::autoDetectCanUseLimitSubqueryWalker(
                        $queryAST,
                        $rootAlias,
                        $lazyEntityAliasesToClassMetadataMap,
                        $toManyJoinsAliases,
                        $selectFieldIdentificationVariablesToSelectExpressionsMap,
                    );

                    return $queryFeatures;
                }

                // There aren't any ToMany columns usages, so the iteration continues. The grouping node is not
                // however tracked in $groupingEntityAliasesToPathExpressions because at this point it's treated
                // as an "unknown/risky".
            }

            // Case when the grouping uses any columns from any of the ToMany joins: nothing more to do here.
            if (
                $queryFeatures['queryGroupingCouldUseColumnsFromToManyJoins']
                && array_intersect($toManyJoinsAliases, array_keys($groupingEntityAliasesToPathExpressions))
            ) {
                $queryFeatures['resultQueryCanUseLimitSubqueryWalker'] = self::autoDetectCanUseLimitSubqueryWalker(
                    $queryAST,
                    $rootAlias,
                    $lazyEntityAliasesToClassMetadataMap,
                    $toManyJoinsAliases,
                    $selectFieldIdentificationVariablesToSelectExpressionsMap,
                );

                return $queryFeatures;
            }

            $queryFeatures['queryGroupingCouldUseColumnsFromToManyJoins'] = false;
            $queryFeatures['couldProduceDuplicates']                      = false;

            // Case when no tracked path expressions: return immediately. This could happen only if the grouping
            // uses an expression, which isn't safe to work with any further (unfortunately).
            if (! $groupingEntityAliasesToPathExpressions) {
                return $queryFeatures;
            }

            // Check whether the grouping will produce the same number of groups as the count of all root entity records.
            // This can happen only if at least one of the grouping items is a unique and not-nullable column. This
            // includes (complete) primary keys. Note: the order of columns in the grouping (e.g. the GroupBy clause)
            // is not relevant. This allows to short-circuit this lookup on the first condition that satisfies the
            // search.
            /** @var array<string, string[]> $groupingIdentifierFieldNamesByEntityAliasToRecheck */
            $groupingIdentifierFieldNamesByEntityAliasToRecheck = [];
            foreach ($groupingEntityAliasesToPathExpressions as $groupingEntityAlias => $groupingPathExpressions) {
                // Case when no PathExpression: grouping uses an entire entity, including its primary key.
                if (! $groupingPathExpressions) {
                    $groupingIdentifierFieldNamesByEntityAliasToRecheck = [];

                    break;
                }

                foreach ($groupingPathExpressions as $groupingPathExpression) {
                    // Case when no field on the PathExpression: grouping uses an entire entity, including its primary key.
                    if ($groupingPathExpression->field === null) {
                        $groupingIdentifierFieldNamesByEntityAliasToRecheck = [];

                        break 2;
                    }

                    $entityClassMetadata = $lazyEntityAliasesToClassMetadataMap->getClassMetadata($groupingEntityAlias);

                    // Case when the field is unique and not nullable: the counts will be equal.
                    if (
                        $entityClassMetadata->isUniqueField($groupingPathExpression->field)
                        && ! $entityClassMetadata->isNullable($groupingPathExpression->field)
                    ) {
                        $groupingIdentifierFieldNamesByEntityAliasToRecheck = [];

                        break 2;
                    }

                    // Case when the field is a primary key: collect it to recheck it later in case it's a composite primary
                    // key.
                    if ($entityClassMetadata->isIdentifier($groupingPathExpression->field)) {
                        $groupingIdentifierFieldNamesByEntityAliasToRecheck[$groupingEntityAlias][] = $groupingPathExpression->field;

                        continue;
                    }

                    // Any other case: the counts will not be equal.
                    return $queryFeatures;
                }
            }

            // Recheck the primary key fields presence.
            foreach ($groupingIdentifierFieldNamesByEntityAliasToRecheck as $entityAlias => $presentIdentifierFieldNames) {
                $presentIdentifierFieldNames = array_unique($presentIdentifierFieldNames);

                $entityClassMetadata = $lazyEntityAliasesToClassMetadataMap->getClassMetadata($entityAlias);

                /// Case when the grouping uses primary keys from an entity, but not all of them: return.
                if (
                    count(array_intersect($presentIdentifierFieldNames, $entityClassMetadata->getIdentifierFieldNames()))
                    !== count($entityClassMetadata->getIdentifierFieldNames())
                ) {
                    return $queryFeatures;
                }
            }

            $queryFeatures['queryGroupingProducesGroupsCountEqualToRootEntityRecordsCount'] = true;

            // The usage of grouping conclusively determines whether there "could be duplicates" (or the
            // query itself is invalid because it selects columns that are not in the GroupBy and are not in an aggregate
            // function).
            return $queryFeatures;
        }

        // Case when the query doesn't SELECT DISTINCT and doesn't use GROUP BY, and when it SELECTs only scalar values
        // (including expressions), then it's considered a "plain select scalars" query.
        if (! $anyEntityAliasPresentInSelectClause) {
            $queryFeatures['queryIsPlainSelectScalars'] = true;

            return $queryFeatures;
        }

        // When no ToMany joins (and no multiple FROMs), then a DQL query cannot produce duplicates. In particular:
        // DQL queries disallow the usage of "rows generating" functions (that can appear in the Select list or
        // through joins).
        if (! $queryFeatures['couldHaveToManyJoins']) {
            $queryFeatures['couldProduceDuplicates'] = false;

            return $queryFeatures;
        }

        // When there are ToMany joins, and no GroupBy clause, then the Select list HAS TO NOT use any of the columns
        // from the ToMany relations, and it HAS TO use "SELECT DISTINCT"

        if (! $queryAST->selectClause->isDistinct) {
            $queryFeatures['resultQueryCanUseLimitSubqueryWalker'] = self::autoDetectCanUseLimitSubqueryWalker(
                $queryAST,
                $rootAlias,
                $lazyEntityAliasesToClassMetadataMap,
                $toManyJoinsAliases,
            );

            return $queryFeatures;
        }

        // Check the Select list.
        foreach ($queryAST->selectClause->selectExpressions as $selectExpression) {
            if (! $selectExpression instanceof SelectExpression) {
                return $queryFeatures;
            }

            // Case when the expression is a bare entity alias, it must not be an alias from a ToMany relation
            if (is_string($selectExpression->expression)) {
                if (in_array($selectExpression->expression, $toManyJoinsAliases, true)) {
                    return $queryFeatures;
                }

                continue;
            }

            // Search for usage of any of the ToMany columns.
            $searchResult = self::searchEntityAliasesUsageInASTNode($toManyJoinsAliases, $selectExpression->expression);

            // Case when the expression is too complex: It's not safe to make any assumptions about what is being
            // Selected. Return immediately.
            if ($searchResult === null) {
                return $queryFeatures;
            }

            // Case when there is at least one ToMany column usage: return immediately.
            if ($searchResult === true) {
                $queryFeatures['resultQueryCanUseLimitSubqueryWalker'] = self::autoDetectCanUseLimitSubqueryWalker(
                    $queryAST,
                    $rootAlias,
                    $lazyEntityAliasesToClassMetadataMap,
                    $toManyJoinsAliases,
                );

                return $queryFeatures;
            }
        }

        $queryFeatures['couldProduceDuplicates'] = false;

        return $queryFeatures;
    }

    /**
     * @param string[] $searchedEntityAliases The entity aliases to find anywhere in the Node
     * @param mixed    $node                  Mostly instances of Doctrine\ORM\Query\AST\Node but could be anything
     * @param int      $remainingDepth        When reaches 0, the recursion is shorted for safety with no conclusion.
     *
     * @return bool|null When bool: whether at least one usage found. When null: the function was shorted for safety
     *                   and its result should not be relied upon.
     */
    private static function searchEntityAliasesUsageInASTNode(
        array $searchedEntityAliases,
        mixed $node,
        int $remainingDepth = 20,
    ): bool|null {
        if ($node instanceof Query\AST\PathExpression) {
            return in_array($node->identificationVariable, $searchedEntityAliases, true);
        }

        if ($remainingDepth === 0) {
            // Too deep: exit the recursion for safety.
            return null;
        }

        if ($node instanceof Node) {
            foreach ((new ReflectionClass($node))->getProperties(ReflectionProperty::IS_PUBLIC) as $property) {
                $result = self::searchEntityAliasesUsageInASTNode($searchedEntityAliases, $property->getValue($node), $remainingDepth - 1);

                if ($result === null || $result === true) {
                    return $result;
                }
            }
        } elseif (is_array($node)) {
            foreach ($node as $item) {
                // $remainingDepth not decremented here because arrays of Nodes aren't worthwhile "counting"
                $result = self::searchEntityAliasesUsageInASTNode($searchedEntityAliases, $item, $remainingDepth);

                if ($result === null || $result === true) {
                    return $result;
                }
            }
        }

        // For all other "Node cases", there necessarily must not be any mentions of entity aliases.
        return false;
    }

    /**
     * Check whether the query can use the {@see LimitSubqueryWalker}.
     *
     * Roughly follows what LimitSubqueryWalker::validate() does, plus some of the "exception cases".
     *
     * @param string[] $toManyJoinsAliases
     *
     * @return bool|null Null means "undetermined"
     */
    private static function autoDetectCanUseLimitSubqueryWalker(
        Query\AST\SelectStatement $queryAST,
        string $rootAlias,
        LazyEntityAliasesToClassMetadataMap $lazyEntityAliasesToClassMetadataMap,
        array $toManyJoinsAliases,
        SelectFieldIdentificationVariablesToSelectExpressionsMap|null $selectExpressionsByFieldIdentificationVariable = null,
    ): bool|null {
        $rootClassMetadata = $lazyEntityAliasesToClassMetadataMap->getClassMetadata($rootAlias);

        // The primary key of the root entity must be single-column and must not be a foreign key (aka. "an association").
        if (
            $rootClassMetadata->isIdentifierComposite
            || $rootClassMetadata->hasAssociation($rootClassMetadata->getSingleIdentifierFieldName())
        ) {
            return false;
        }

        // None of the OrderBy items must be using a ToMany column

        // Case when no OrderBy clause or no ToMany joins: can use the LimitSubqueryWalker.
        if ($queryAST->orderByClause === null || count($toManyJoinsAliases) === 0) {
            return true;
        }

        // The processing of an OrderyBy clause is similar to that of GroupBy.
        foreach ($queryAST->orderByClause->orderByItems as $orderByItem) {
            $orderByItemExpression = $orderByItem->expression;

            if (is_string($orderByItemExpression)) {
                // Case when this is an entity alias
                if ($lazyEntityAliasesToClassMetadataMap->hasEntityAlias($orderByItemExpression)) {
                    if (in_array($orderByItemExpression, $toManyJoinsAliases, true)) {
                        return false;
                    }

                    continue;
                }

                // Populate a helper map
                if (! $selectExpressionsByFieldIdentificationVariable) {
                    $selectExpressionsByFieldIdentificationVariable = new SelectFieldIdentificationVariablesToSelectExpressionsMap($queryAST);
                }

                // Case when this is an alias from the Select list: see what the Select item is.
                /** @var Query\AST\PathExpression|mixed|null $usedSelectExpressionExpression */
                $usedSelectExpressionExpression = $selectExpressionsByFieldIdentificationVariable->getSelectExpression($orderByItemExpression)->expression ?? null;
                if (! $usedSelectExpressionExpression) {
                    // The OrderBy uses an alias that is unrecognized. This should never happen (because it would be
                    // a SQL/DQL syntax error).
                    throw new RuntimeException('OrderBy clause uses an unrecognized alias: ' . $orderByItemExpression);
                }

                // Treat the "extracted" Select expression as the orderByItemExpression and let the rest of the foreach code
                // handle it.
                $orderByItemExpression = $usedSelectExpressionExpression;
            }

            if ($orderByItemExpression instanceof Query\AST\PathExpression) {
                if (in_array($orderByItemExpression->identificationVariable, $toManyJoinsAliases, true)) {
                    return false;
                }

                continue;
            }

            // At this point, the orderByItemExpression is an expression. The same caveats and decisions are made here as for
            // the same situation in the GroupBy case.

            $searchResult = self::searchEntityAliasesUsageInASTNode($toManyJoinsAliases, $orderByItemExpression);

            // Case when the expression is too complex: It's not safe to make any assumptions about what is being
            // ordered by on.
            if ($searchResult === null) {
                return null;
            }

            // Case when there is at least one ToMany column usage: cannot use the LimitSubqueryWalker.
            if ($searchResult === true) {
                return false;
            }
        }

        return true;
    }

    /**
     * @deprecated Use the individual ::get*OutputWalker()
     *
     * Returns whether the paginator will use an output walker.
     */
    public function getUseOutputWalkers(): bool|null
    {
        return $this->getUseResultQueryOutputWalker() === null && $this->getUseCountQueryOutputWalker() === null
            ? null
            : $this->getUseResultQueryOutputWalker() && $this->getUseCountQueryOutputWalker();
    }

    /**
     * @deprecated Use the individual ::set*OutputWalker()
     *
     * Sets whether the paginator will use an output walker.
     *
     * @return $this
     */
    public function setUseOutputWalkers(bool|null $useOutputWalkers): static
    {
        $this->setUseResultQueryOutputWalker($useOutputWalkers);
        $this->setUseCountQueryOutputWalker($useOutputWalkers);

        return $this;
    }

    public function getUseResultQueryOutputWalker(): bool|null
    {
        return $this->useResultQueryOutputWalker;
    }

    public function setUseResultQueryOutputWalker(bool|null $useResultQueryOutputWalker): static
    {
        $this->useResultQueryOutputWalker = $useResultQueryOutputWalker;

        return $this;
    }

    public function getUseCountQueryOutputWalker(): bool|null
    {
        return $this->useCountQueryOutputWalker;
    }

    public function setUseCountQueryOutputWalker(bool|null $useCountQueryOutputWalker): static
    {
        $this->useCountQueryOutputWalker = $useCountQueryOutputWalker;

        return $this;
    }

    /** @internal */
    public function getDefaultCountWalkerNeedsToUseCountDistinct(): bool
    {
        return $this->defaultCountWalkerNeedsToUseCountDistinct;
    }

    /** @internal */
    public function getCountWalkerShouldDropGroupByClause(): bool
    {
        return $this->countWalkerShouldDropGroupByClause;
    }

    /**
     * Determines whether to use an output-equivalent query walker for the pagination result query or the count query.
     */
    private function useOutputWalker(Query $query, bool $forCountQuery = false): bool
    {
        if (! $forCountQuery && $this->useResultQueryOutputWalker !== null) {
            return $this->useResultQueryOutputWalker;
        }

        if ($forCountQuery && $this->useCountQueryOutputWalker !== null) {
            return $this->useCountQueryOutputWalker;
        }

        // Auto-decide: when a custom output walker already present, then do not use the Paginator's.
        return $query->getHint(Query::HINT_CUSTOM_OUTPUT_WALKER) === false;
    }

    private function cloneQuery(Query $query): Query
    {
        $cloneQuery = clone $query;

        $cloneQuery->setParameters(clone $query->getParameters());
        $cloneQuery->setCacheable(false);

        foreach ($query->getHints() as $name => $value) {
            $cloneQuery->setHint($name, $value);
        }

        return $cloneQuery;
    }

    /**
     * @param mixed[] $identifiers
     *
     * @return mixed[]
     */
    private function convertWhereInIdentifiersToDatabaseValues(array $identifiers): array
    {
        $query = $this->cloneQuery($this->query);
        $query->setHint(Query::HINT_CUSTOM_OUTPUT_WALKER, RootTypeWalker::class);

        $connection = $this->query->getEntityManager()->getConnection();
        $type       = $query->getSQL();
        assert(is_string($type));

        return array_map(static fn ($id): mixed => $connection->convertToDatabaseValue($id, $type), $identifiers);
    }

    private function getCountQuery(): Query
    {
        /*
            As opposed to using self::cloneQuery, the following code does not transfer
            a potentially existing result set mapping (either set directly by the user,
            or taken from the parser result from a previous invocation of Query::parse())
            to the new query object. This is fine, since we are going to completely change the
            select clause, so a previously existing result set mapping (RSM) is probably wrong anyway.
            In the case of using output walkers, we are even creating a new RSM down below.
            In the case of using a tree walker, we want to have a new RSM created by the parser.
        */
        $countQuery = new Query($this->query->getEntityManager());
        $countQuery->setDQL($this->query->getDQL());
        $countQuery->setParameters(clone $this->query->getParameters());
        $countQuery->setCacheable(false);
        foreach ($this->query->getHints() as $name => $value) {
            $countQuery->setHint($name, $value);
        }

        if ($this->useOutputWalker($countQuery, forCountQuery: true)) {
            $platform = $countQuery->getEntityManager()->getConnection()->getDatabasePlatform(); // law of demeter win

            $rsm = new ResultSetMapping();
            $rsm->addScalarResult($this->getSQLResultCasing($platform, 'dctrn_count'), 'count');

            $countQuery->setHint(Query::HINT_CUSTOM_OUTPUT_WALKER, CountOutputWalker::class);
            $countQuery->setResultSetMapping($rsm);
        } else {
            $this->appendTreeWalker($countQuery, CountWalker::class);
            $this->unbindUnusedQueryParams($countQuery);

            if (! $countQuery->hasHint(CountWalker::HINT_DISTINCT)) {
                $countQuery->setHint(CountWalker::HINT_DISTINCT, $this->defaultCountWalkerNeedsToUseCountDistinct);
            }

            $countQuery->setHint(CountWalker::HINT_DROP_GROUP_BY_CLAUSE, $this->countWalkerShouldDropGroupByClause);
        }

        $countQuery->setFirstResult(0)->setMaxResults(null);

        return $countQuery;
    }

    /**
     * Appends a custom tree walker to the tree walkers hint.
     *
     * @param class-string<TreeWalker> $walkerClass
     */
    private function appendTreeWalker(Query $query, string $walkerClass): void
    {
        $hints = $query->getHint(Query::HINT_CUSTOM_TREE_WALKERS);

        if ($hints === false) {
            $hints = [];
        }

        $hints[] = $walkerClass;
        $query->setHint(Query::HINT_CUSTOM_TREE_WALKERS, $hints);
    }

    private function unbindUnusedQueryParams(Query $query): void
    {
        $parser            = new Parser($query);
        $parameterMappings = $parser->parse()->getParameterMappings();
        /** @var ArrayCollection<int, Parameter> $parameters */
        $parameters = $query->getParameters();

        foreach ($parameters as $key => $parameter) {
            $parameterName = $parameter->getName();

            if (! (isset($parameterMappings[$parameterName]) || array_key_exists($parameterName, $parameterMappings))) {
                unset($parameters[$key]);
            }
        }

        $query->setParameters($parameters);
    }
}
