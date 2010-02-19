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
 * <http://www.phpdoctrine.org>.
 */

namespace Doctrine\ORM\Query\AST;

/**
 * Join ::= ["LEFT" ["OUTER"] | "INNER"] "JOIN" JoinAssociationPathExpression
 *          ["AS"] AliasIdentificationVariable [("ON" | "WITH") ConditionalExpression]
 *
 * @license http://www.opensource.org/licenses/lgpl-license.php LGPL
 * @link    www.doctrine-project.org
 * @since   2.0
 * @version $Revision: 3938 $
 * @author  Guilherme Blanco <guilhermeblanco@hotmail.com>
 * @author  Jonathan Wage <jonwage@gmail.com>
 * @author  Roman Borschel <roman@code-factory.org>
 */
class Join extends Node
{
    const JOIN_TYPE_LEFT = 1;
    const JOIN_TYPE_LEFTOUTER = 2;
    const JOIN_TYPE_INNER = 3;

    public $joinType = self::JOIN_TYPE_INNER;    
    public $joinAssociationPathExpression = null;
    public $aliasIdentificationVariable = null;
    public $conditionalExpression = null;

    public function __construct($joinType, $joinAssocPathExpr, $aliasIdentVar)
    {
        $this->joinType = $joinType;
        $this->joinAssociationPathExpression = $joinAssocPathExpr;
        $this->aliasIdentificationVariable = $aliasIdentVar;
    }
    
    public function dispatch($sqlWalker)
    {
        return $sqlWalker->walkJoin($this);
    }
}