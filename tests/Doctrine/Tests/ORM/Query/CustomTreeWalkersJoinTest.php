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

namespace Doctrine\Tests\ORM\Query;

use Doctrine\ORM\Query;

/**
 * Test case for custom AST walking and adding new joins.
 *
 * @author      Lukasz Cybula <lukasz.cybula@fsi.pl>
 * @license     MIT
 * @link        http://www.doctrine-project.org
 */
class CustomTreeWalkersJoinTest extends \Doctrine\Tests\OrmTestCase
{
    private $em;

    protected function setUp()
    {
        $this->em = $this->_getTestEntityManager();
    }

    public function assertSqlGeneration($dqlToBeTested, $sqlToBeConfirmed)
    {
        try {
            $query = $this->em->createQuery($dqlToBeTested);
            $query->setHint(Query::HINT_CUSTOM_TREE_WALKERS, array('Doctrine\Tests\ORM\Query\CustomTreeWalkerJoin'))
                  ->useQueryCache(false);

            $this->assertEquals($sqlToBeConfirmed, $query->getSql());
            $query->free();
        } catch (\Exception $e) {
            $this->fail($e->getMessage() . ' at "' . $e->getFile() . '" on line ' . $e->getLine());

        }
    }

    public function testAddsJoin()
    {
        $this->assertSqlGeneration(
            'select u from Doctrine\Tests\Models\CMS\CmsUser u',
            "SELECT c0_.id AS id_0, c0_.status AS status_1, c0_.username AS username_2, c0_.name AS name_3, c1_.id AS id_4, c1_.country AS country_5, c1_.zip AS zip_6, c1_.city AS city_7, c0_.email_id AS email_id_8, c1_.user_id AS user_id_9 FROM cms_users c0_ LEFT JOIN cms_addresses c1_ ON c0_.id = c1_.user_id"
        );
    }

    public function testDoesNotAddJoin()
    {
        $this->assertSqlGeneration(
            'select a from Doctrine\Tests\Models\CMS\CmsAddress a',
            "SELECT c0_.id AS id_0, c0_.country AS country_1, c0_.zip AS zip_2, c0_.city AS city_3, c0_.user_id AS user_id_4 FROM cms_addresses c0_"
        );
    }
}

class CustomTreeWalkerJoin extends Query\TreeWalkerAdapter
{
    public function walkSelectStatement(Query\AST\SelectStatement $selectStatement)
    {
        foreach ($selectStatement->fromClause->identificationVariableDeclarations as $identificationVariableDeclaration) {
            $rangeVariableDecl = $identificationVariableDeclaration->rangeVariableDeclaration;
            
            if ($rangeVariableDecl->abstractSchemaName !== 'Doctrine\Tests\Models\CMS\CmsUser') {
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
        $userMetadata    = $entityManager->getClassMetadata('Doctrine\Tests\Models\CMS\CmsUser');
        $addressMetadata = $entityManager->getClassMetadata('Doctrine\Tests\Models\CMS\CmsAddress');

        $this->setQueryComponent($rangeVariableDecl->aliasIdentificationVariable . 'a',
            array(
                'metadata'     => $addressMetadata,
                'parent'       => $rangeVariableDecl->aliasIdentificationVariable,
                'relation'     => $userMetadata->getAssociationMapping('address'),
                'map'          => null,
                'nestingLevel' => 0,
                'token'        => null,
            )
        );
    }
}
