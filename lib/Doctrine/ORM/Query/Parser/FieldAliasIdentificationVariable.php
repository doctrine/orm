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

namespace Doctrine\ORM\Query\Parser;

/**
 * FieldAliasIdentificationVariable ::= identifier
 *
 * @author      Guilherme Blanco <guilhermeblanco@hotmail.com>
 * @author      Janne Vanhala <jpvanhal@cc.hut.fi>
 * @license     http://www.opensource.org/licenses/lgpl-license.php LGPL
 * @link        http://www.phpdoctrine.org
 * @since       2.0
 * @version     $Revision$
 */
class FieldAliasIdentificationVariable extends \Doctrine\ORM\Query\Parser
{
    protected $_fieldAlias = null;
    
    
    public function syntax()
    {
        // AliasIdentificationVariable = identifier
        $this->_parser->match(\Doctrine\ORM\Query\Token::T_IDENTIFIER);
        $this->_fieldAlias = $this->_parser->token['value'];
    }


    public function semantical()
    {
        $parserResult = $this->_parser->getParserResult();
    
        if ($parserResult->hasFieldAlias($this->_fieldAlias)) {
            // We should throw semantical error if there's already a field for this alias
            $message  = "Cannot re-declare field alias '" . $this->_fieldAlias 
                      . "' near '" . $this->_parser->getQueryPiece($this->_parser->token) . "'.";

            $this->_parser->semanticalError($message);
        }

        return $this->_fieldAlias;
    }
}