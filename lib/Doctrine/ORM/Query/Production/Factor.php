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

namespace Doctrine\Query\Production;

/**
 * Factor = [("+" | "-")] Primary
 *
 * @package     Doctrine
 * @subpackage  Query
 * @author      Guilherme Blanco <guilhermeblanco@hotmail.com>
 * @author      Janne Vanhala <jpvanhal@cc.hut.fi>
 * @license     http://www.opensource.org/licenses/lgpl-license.php LGPL
 * @link        http://www.phpdoctrine.org
 * @since       2.0
 * @version     $Revision$
 */
class Factor extends \Doctrine\Query\Production
{
    protected $_type;

    protected $_primary;

    public function syntax($paramHolder)
    {
        // Factor = [("+" | "-")] Primary
        if ($this->_isNextToken('+')) {
            $this->_parser->match('+');
            $this->_type = '+';
        } elseif ($this->_isNextToken('-')) {
            $this->_parser->match('-');
            $this->_type = '-';
        }

        $this->_primary = $this->AST('Primary', $paramHolder);

        // Optimize depth instances in AST
        if ($this->_type === null) {
            return $this->_primary;
        }
    }

    public function semantical($paramHolder)
    {
        $this->_primary->semantical($paramHolder);
    }

    public function buildSql()
    {
        return $this->_type . ' ' . $this->_primary->buildSql();
    }
    
    /**
     * Visitor support
     *
     * @param object $visitor
     */
    public function accept($visitor)
    {
        $this->_primary->accept($visitor);
        $visitor->visitFactor($this);
    }
    
    /* Getters */
    
    public function getType()
    {
        return $this->_type;
    }
    
    public function getPrimary()
    {
        return $this->_primary;
    }
}