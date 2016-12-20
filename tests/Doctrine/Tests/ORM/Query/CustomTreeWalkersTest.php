<?php

namespace Doctrine\Tests\ORM\Functional;

use Doctrine\ORM\Query;
use Doctrine\ORM\Query\QueryException;
use Doctrine\Tests\Models\CMS\CmsAddress;
use Doctrine\Tests\Models\CMS\CmsUser;
use Doctrine\Tests\OrmTestCase;

/**
 * Test case for custom AST walking and modification.
 *
 * @author      Roman Borschel <roman@code-factory.org>
 * @license     http://www.opensource.org/licenses/lgpl-license.php LGPL
 * @link        http://www.doctrine-project.org
 * @since       2.0
 */
class CustomTreeWalkersTest extends OrmTestCase
{
    private $_em;

    protected function setUp()
    {
        $this->_em = $this->_getTestEntityManager();
    }

    public function generateSql($dqlToBeTested, $treeWalkers, $outputWalker)
    {
        $query = $this->_em->createQuery($dqlToBeTested);
        $query->setHint(Query::HINT_CUSTOM_TREE_WALKERS, $treeWalkers)
            ->useQueryCache(false);

        if ($outputWalker) {
            $query->setHint(Query::HINT_CUSTOM_OUTPUT_WALKER, $outputWalker);
        }

        return $query->getSql();
    }

    public function assertSqlGeneration($dqlToBeTested, $sqlToBeConfirmed, $treeWalkers = [], $outputWalker = null)
    {
        try {
            $this->assertEquals($sqlToBeConfirmed, $this->generateSql($dqlToBeTested, $treeWalkers, $outputWalker));
        } catch (\Exception $e) {
            $this->fail($e->getMessage() . ' at "' . $e->getFile() . '" on line ' . $e->getLine());
        }
    }

    public function testSupportsQueriesWithoutWhere()
    {
        $this->assertSqlGeneration(
            'select u from Doctrine\Tests\Models\CMS\CmsUser u',
            "SELECT c0_.id AS id_0, c0_.status AS status_1, c0_.username AS username_2, c0_.name AS name_3, c0_.email_id AS email_id_4 FROM cms_users c0_ WHERE c0_.id = 1",
            [CustomTreeWalker::class]
        );
    }

    public function testSupportsQueriesWithMultipleConditionalExpressions()
    {
        $this->assertSqlGeneration(
            'select u from Doctrine\Tests\Models\CMS\CmsUser u where u.name = :name or u.name = :otherName',
            "SELECT c0_.id AS id_0, c0_.status AS status_1, c0_.username AS username_2, c0_.name AS name_3, c0_.email_id AS email_id_4 FROM cms_users c0_ WHERE (c0_.name = ? OR c0_.name = ?) AND c0_.id = 1",
            [CustomTreeWalker::class]
        );
    }

    public function testSupportsQueriesWithSimpleConditionalExpression()
    {
        $this->assertSqlGeneration(
            'select u from Doctrine\Tests\Models\CMS\CmsUser u where u.name = :name',
            "SELECT c0_.id AS id_0, c0_.status AS status_1, c0_.username AS username_2, c0_.name AS name_3, c0_.email_id AS email_id_4 FROM cms_users c0_ WHERE c0_.name = ? AND c0_.id = 1",
            [CustomTreeWalker::class]
        );
    }

    public function testSetUnknownQueryComponentThrowsException()
    {
        $this->expectException(QueryException::class);
        $this->expectExceptionMessage("Invalid query component given for DQL alias 'x', requires 'metadata', 'parent', 'relation', 'map', 'nestingLevel' and 'token' keys.");

        $this->generateSql(
            'select u from Doctrine\Tests\Models\CMS\CmsUser u',
            [],
            AddUnknownQueryComponentWalker::class
        );
    }

    public function testSupportsSeveralHintsQueries()
    {
        $this->assertSqlGeneration(
            'select u from Doctrine\Tests\Models\CMS\CmsUser u',
            "SELECT c0_.id AS id_0, c0_.status AS status_1, c0_.username AS username_2, c0_.name AS name_3, c1_.id AS id_4, c1_.country AS country_5, c1_.zip AS zip_6, c1_.city AS city_7, c0_.email_id AS email_id_8, c1_.user_id AS user_id_9 FROM cms_users c0_ LEFT JOIN cms_addresses c1_ ON c0_.id = c1_.user_id WHERE c0_.id = 1",
            [CustomTreeWalkerJoin::class, CustomTreeWalker::class]
        );
    }
}

class AddUnknownQueryComponentWalker extends Query\SqlWalker
{
    public function walkSelectStatement(Query\AST\SelectStatement $selectStatement)
    {
        parent::walkSelectStatement($selectStatement);

        $this->setQueryComponent('x', []);
    }
}

class CustomTreeWalker extends Query\TreeWalkerAdapter
{
    public function walkSelectStatement(Query\AST\SelectStatement $selectStatement)
    {
        // Get the DQL aliases of all the classes we want to modify
        $dqlAliases = [];

        foreach ($this->_getQueryComponents() as $dqlAlias => $comp) {
            // Hard-coded check just for demonstration: We want to modify the query if
            // it involves the CmsUser class.
            if ($comp['metadata']->name == CmsUser::class) {
                $dqlAliases[] = $dqlAlias;
            }
        }

        // Create our conditions for all involved classes
        $factors = [];
        foreach ($dqlAliases as $alias) {
            $pathExpr = new Query\AST\PathExpression(Query\AST\PathExpression::TYPE_STATE_FIELD, $alias, 'id');
            $pathExpr->type = Query\AST\PathExpression::TYPE_STATE_FIELD;
            $comparisonExpr = new Query\AST\ComparisonExpression($pathExpr, '=', 1);

            $condPrimary = new Query\AST\ConditionalPrimary;
            $condPrimary->simpleConditionalExpression = $comparisonExpr;

            $factor = new Query\AST\ConditionalFactor($condPrimary);
            $factors[] = $factor;
        }

        if (($whereClause = $selectStatement->whereClause) !== null) {
            // There is already a WHERE clause, so append the conditions
            $condExpr = $whereClause->conditionalExpression;

            // Since Phase 1 AST optimizations were included, we need to re-add the ConditionalExpression
            if ( ! ($condExpr instanceof Query\AST\ConditionalExpression)) {
                $condExpr = new Query\AST\ConditionalExpression([$condExpr]);

                $whereClause->conditionalExpression = $condExpr;
            }

            $existingTerms = $whereClause->conditionalExpression->conditionalTerms;

            if (count($existingTerms) > 1) {
                // More than one term, so we need to wrap all these terms in a single root term
                // i.e: "WHERE u.name = :foo or u.other = :bar" => "WHERE (u.name = :foo or u.other = :bar) AND <our condition>"

                $primary = new Query\AST\ConditionalPrimary;
                $primary->conditionalExpression = new Query\AST\ConditionalExpression($existingTerms);
                $existingFactor = new Query\AST\ConditionalFactor($primary);
                $term = new Query\AST\ConditionalTerm(array_merge([$existingFactor], $factors));

                $selectStatement->whereClause->conditionalExpression->conditionalTerms = [$term];
            } else {
                // Just one term so we can simply append our factors to that term
                $singleTerm = $selectStatement->whereClause->conditionalExpression->conditionalTerms[0];

                // Since Phase 1 AST optimizations were included, we need to re-add the ConditionalExpression
                if ( ! ($singleTerm instanceof Query\AST\ConditionalTerm)) {
                    $singleTerm = new Query\AST\ConditionalTerm([$singleTerm]);

                    $selectStatement->whereClause->conditionalExpression->conditionalTerms[0] = $singleTerm;
                }

                $singleTerm->conditionalFactors = array_merge($singleTerm->conditionalFactors, $factors);
                $selectStatement->whereClause->conditionalExpression->conditionalTerms = [$singleTerm];
            }
        } else {
            // Create a new WHERE clause with our factors
            $term = new Query\AST\ConditionalTerm($factors);
            $condExpr = new Query\AST\ConditionalExpression([$term]);
            $whereClause = new Query\AST\WhereClause($condExpr);
            $selectStatement->whereClause = $whereClause;
        }
    }
}

class CustomTreeWalkerJoin extends Query\TreeWalkerAdapter
{
    public function walkSelectStatement(Query\AST\SelectStatement $selectStatement)
    {
        foreach ($selectStatement->fromClause->identificationVariableDeclarations as $identificationVariableDeclaration) {
            $rangeVariableDecl = $identificationVariableDeclaration->rangeVariableDeclaration;

            if ($rangeVariableDecl->abstractSchemaName !== CmsUser::class) {
                continue;
            }

            $this->modifySelectStatement($selectStatement, $identificationVariableDeclaration);
        }
    }

    private function modifySelectStatement(Query\AST\SelectStatement $selectStatement, $identificationVariableDecl)
    {
        $rangeVariableDecl       = $identificationVariableDecl->rangeVariableDeclaration;
        $joinAssocPathExpression = new Query\AST\JoinAssociationPathExpression($rangeVariableDecl->aliasIdentificationVariable, 'address');
        $joinAssocDeclaration    = new Query\AST\JoinAssociationDeclaration($joinAssocPathExpression, $rangeVariableDecl->aliasIdentificationVariable . 'a', null);
        $join                    = new Query\AST\Join(Query\AST\Join::JOIN_TYPE_LEFT, $joinAssocDeclaration);
        $selectExpression        = new Query\AST\SelectExpression($rangeVariableDecl->aliasIdentificationVariable . 'a', null, false);

        $identificationVariableDecl->joins[]                = $join;
        $selectStatement->selectClause->selectExpressions[] = $selectExpression;

        $entityManager   = $this->_getQuery()->getEntityManager();
        $userMetadata    = $entityManager->getClassMetadata(CmsUser::class);
        $addressMetadata = $entityManager->getClassMetadata(CmsAddress::class);

        $this->setQueryComponent($rangeVariableDecl->aliasIdentificationVariable . 'a',
            [
                'metadata'     => $addressMetadata,
                'parent'       => $rangeVariableDecl->aliasIdentificationVariable,
                'relation'     => $userMetadata->getAssociationMapping('address'),
                'map'          => null,
                'nestingLevel' => 0,
                'token'        => null,
            ]
        );
    }
}
