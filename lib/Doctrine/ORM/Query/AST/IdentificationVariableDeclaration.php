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

namespace Doctrine\ORM\Query\AST;

/**
 * IdentificationVariableDeclaration ::= RangeVariableDeclaration [IndexBy] {JoinVariableDeclaration}*
 *
 * @author      Guilherme Blanco <guilhermeblanco@hotmail.com>
 * @license     http://www.opensource.org/licenses/lgpl-license.php LGPL
 * @link        http://www.doctrine-project.org
 * @since       2.0
 * @version     $Revision$
 */
class IdentificationVariableDeclaration extends Node
{
    protected $_rangeVariableDeclaration = null;
    
    protected $_indexBy = null;

    protected $_joinVariableDeclarations = array();

    public function __construct($rangeVariableDecl, $indexBy, array $joinVariableDecls)
    {
        $this->_rangeVariableDeclaration = $rangeVariableDecl;
        $this->_indexBy = $indexBy;
        $this->_joinVariableDeclarations = $joinVariableDecls;
    }
    
    /* Getters */
    public function getRangeVariableDeclaration()
    {
        return $this->_rangeVariableDeclaration;
    }


    public function getIndexBy()
    {
        return $this->_indexBy;
    }
    

    public function getJoinVariableDeclarations()
    {
        return $this->_joinVariableDeclarations;
    }

    /* REMOVE ME LATER. COPIED METHODS FROM SPLIT OF PRODUCTION INTO "AST" AND "PARSER" */
    
    public function buildSql()
    {
        $str = $this->_rangeVariableDeclaration->buildSql();

        for ($i = 0, $l = count($this->_joinVariableDeclarations); $i < $l; $i++) {
            $str .= ' ' . $this->_joinVariableDeclarations[$i]->buildSql();
        }

        return $str;
    }
}