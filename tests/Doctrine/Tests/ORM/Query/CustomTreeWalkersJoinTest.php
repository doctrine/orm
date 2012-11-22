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
            "SELECT c0_.id AS id0, c0_.status AS status1, c0_.username AS username2, c0_.name AS name3, c1_.id AS id4, c1_.country AS country5, c1_.zip AS zip6, c1_.city AS city7, c0_.email_id AS email_id8, c1_.user_id AS user_id9 FROM cms_users c0_ LEFT JOIN cms_addresses c1_ ON c0_.id = c1_.user_id"
        );
    }

    public function testDoesNotAddJoin()
    {
        $this->assertSqlGeneration(
            'select a from Doctrine\Tests\Models\CMS\CmsAddress a',
            "SELECT c0_.id AS id0, c0_.country AS country1, c0_.zip AS zip2, c0_.city AS city3, c0_.user_id AS user_id4 FROM cms_addresses c0_"
        );
    }
}

class CustomTreeWalkerJoin extends Query\TreeWalkerAdapter
{
    public function walkSelectStatement(Query\AST\SelectStatement $selectStatement)
    {
        foreach ($selectStatement->fromClause->identificationVariableDeclarations as $identificationVariableDeclaration) {
            if ($identificationVariableDeclaration->rangeVariableDeclaration->abstractSchemaName == 'Doctrine\Tests\Models\CMS\CmsUser') {
                $identificationVariableDeclaration->joins[] = new Query\AST\Join(
                    Query\AST\Join::JOIN_TYPE_LEFT,
                    new Query\AST\JoinAssociationDeclaration(
                        new Query\AST\JoinAssociationPathExpression(
                            $identificationVariableDeclaration->rangeVariableDeclaration->aliasIdentificationVariable,
                            'address'
                        ),
                        $identificationVariableDeclaration->rangeVariableDeclaration->aliasIdentificationVariable . 'a',
                        null
                    )
                );
                $selectStatement->selectClause->selectExpressions[] =
                    new Query\AST\SelectExpression(
                        $identificationVariableDeclaration->rangeVariableDeclaration->aliasIdentificationVariable . 'a',
                        null,
                        false
                    );
                $meta1 = $this->_getQuery()->getEntityManager()->getClassMetadata('Doctrine\Tests\Models\CMS\CmsUser');
                $meta = $this->_getQuery()->getEntityManager()->getClassMetadata('Doctrine\Tests\Models\CMS\CmsAddress');
                $this->setQueryComponent($identificationVariableDeclaration->rangeVariableDeclaration->aliasIdentificationVariable . 'a',
                    array(
                        'metadata' => $meta,
                        'parent' => $identificationVariableDeclaration->rangeVariableDeclaration->aliasIdentificationVariable,
                        'relation' => $meta1->getAssociationMapping('address'),
                        'map' => null,
                        'nestingLevel' => 0,
                        'token' => null
                    )
                );
            }
        }
    }
}
