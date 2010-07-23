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

namespace Doctrine\Tests\ORM\Functional;

use Doctrine\ORM\Query;

require_once __DIR__ . '/../../TestInit.php';

/**
 * Test case for custom AST walking and modification.
 *
 * @author      Roman Borschel <roman@code-factory.org>
 * @license     http://www.opensource.org/licenses/lgpl-license.php LGPL
 * @link        http://www.doctrine-project.org
 * @since       2.0
 */
class CustomTreeWalkersTest extends \Doctrine\Tests\OrmFunctionalTestCase
{
    protected function setUp() {
        $this->useModelSet('cms');
        parent::setUp();
    }

    public function assertSqlGeneration($dqlToBeTested, $sqlToBeConfirmed)
    {
        try {
            $query = $this->_em->createQuery($dqlToBeTested);
            $query->setHint(Query::HINT_CUSTOM_TREE_WALKERS, array('Doctrine\Tests\ORM\Functional\CustomTreeWalker'))
                  ->useQueryCache(false);

            $this->assertEquals($sqlToBeConfirmed, $query->getSql());
            $query->free();
        } catch (\Exception $e) {
            $this->fail($e->getMessage() . ' at "' . $e->getFile() . '" on line ' . $e->getLine());

        }
    }

    public function testSupportsQueriesWithoutWhere()
    {
        $this->assertSqlGeneration(
            'select u from Doctrine\Tests\Models\CMS\CmsUser u',
            "SELECT c0_.id AS id0, c0_.status AS status1, c0_.username AS username2, c0_.name AS name3 FROM cms_users c0_ WHERE c0_.id = 1"
        );
    }

    public function testSupportsQueriesWithMultipleConditionalExpressions()
    {
        $this->assertSqlGeneration(
            'select u from Doctrine\Tests\Models\CMS\CmsUser u where u.name = :name or u.name = :otherName',
            "SELECT c0_.id AS id0, c0_.status AS status1, c0_.username AS username2, c0_.name AS name3 FROM cms_users c0_ WHERE (c0_.name = ? OR c0_.name = ?) AND c0_.id = 1"
        );
    }
    
    public function testSupportsQueriesWithSimpleConditionalExpression()
    {
        $this->assertSqlGeneration(
            'select u from Doctrine\Tests\Models\CMS\CmsUser u where u.name = :name',
            "SELECT c0_.id AS id0, c0_.status AS status1, c0_.username AS username2, c0_.name AS name3 FROM cms_users c0_ WHERE c0_.name = ? AND c0_.id = 1"
        );
    }
}

class CustomTreeWalker extends Query\TreeWalkerAdapter
{
    public function walkSelectStatement(Query\AST\SelectStatement $selectStatement)
    {
        // Get the DQL aliases of all the classes we want to modify
        $dqlAliases = array();

        foreach ($this->_getQueryComponents() as $dqlAlias => $comp) {
            // Hard-coded check just for demonstration: We want to modify the query if
            // it involves the CmsUser class.
            if ($comp['metadata']->name == 'Doctrine\Tests\Models\CMS\CmsUser') {
                $dqlAliases[] = $dqlAlias;
            }
        }
        
        // Create our conditions for all involved classes
        $factors = array();
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
                $condExpr = new Query\AST\ConditionalExpression(array($condExpr));

                $whereClause->conditionalExpression = $condExpr;
            }
            
            $existingTerms = $whereClause->conditionalExpression->conditionalTerms;
            
            if (count($existingTerms) > 1) {
                // More than one term, so we need to wrap all these terms in a single root term
                // i.e: "WHERE u.name = :foo or u.other = :bar" => "WHERE (u.name = :foo or u.other = :bar) AND <our condition>"
                
                $primary = new Query\AST\ConditionalPrimary;
                $primary->conditionalExpression = new Query\AST\ConditionalExpression($existingTerms);
                $existingFactor = new Query\AST\ConditionalFactor($primary);
                $term = new Query\AST\ConditionalTerm(array_merge(array($existingFactor), $factors));
                
                $selectStatement->whereClause->conditionalExpression->conditionalTerms = array($term);
            } else {
                // Just one term so we can simply append our factors to that term
                $singleTerm = $selectStatement->whereClause->conditionalExpression->conditionalTerms[0];

                // Since Phase 1 AST optimizations were included, we need to re-add the ConditionalExpression
                if ( ! ($singleTerm instanceof Query\AST\ConditionalTerm)) {
                    $singleTerm = new Query\AST\ConditionalTerm(array($singleTerm));

                    $selectStatement->whereClause->conditionalExpression->conditionalTerms[0] = $singleTerm;
                }

                $singleTerm->conditionalFactors = array_merge($singleTerm->conditionalFactors, $factors);
                $selectStatement->whereClause->conditionalExpression->conditionalTerms = array($singleTerm);
            }
        } else {
            // Create a new WHERE clause with our factors
            $term = new Query\AST\ConditionalTerm($factors);
            $condExpr = new Query\AST\ConditionalExpression(array($term));
            $whereClause = new Query\AST\WhereClause($condExpr);
            $selectStatement->whereClause = $whereClause;
        }
    }
}
