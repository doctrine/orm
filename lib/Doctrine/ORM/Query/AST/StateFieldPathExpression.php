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
 * 	StateFieldPathExpression ::= SimpleStateFieldPathExpression | SimpleStateFieldAssociationPathExpression
 *
 * @author robo
 */
class StateFieldPathExpression extends Node
{
	//const TYPE_COLLECTION_VALUED_ASSOCIATION = 1;
	//const TYPE_SINGLE_VALUED_ASSOCIATION = 2;
	//const TYPE_STATE_FIELD = 3;
	//private $_type;
	
	
    private $_parts;
    // Information that is attached during semantical analysis.
    private $_isSimpleStateFieldPathExpression = false;
    private $_isSimpleStateFieldAssociationPathExpression = false;
    private $_embeddedClassFields = array();
    private $_singleValuedAssociationFields = array();
    private $_collectionValuedAssociationFields = array();

    public function __construct(array $parts)
    {
        $this->_parts = $parts;
    }

    public function getParts() {
        return $this->_parts;
    }

    /**
     * Gets whether the path expression represents a state field that is reached
     * either directly (u.name) or  by navigating over optionally many embedded class instances
     * (u.address.zip).
     *
     * @return boolean
     */
    public function isSimpleStateFieldPathExpression()
    {
        return $this->_isSimpleStateFieldPathExpression;
    }

    /**
     * Gets whether the path expression represents a state field that is reached
     * by navigating over at least one single-valued association and optionally
     * many embedded class instances. (u.Group.address.zip, u.Group.address, ...)
     *
     * @return boolean
     */
    public function isSimpleStateFieldAssociationPathExpression()
    {
        return $this->_isSimpleStateFieldAssociationPathExpression;
    }

    public function isPartEmbeddedClassField($part)
    {
        return isset($this->_embeddedClassFields[$part]);
    }

    public function isPartSingleValuedAssociationField($part)
    {
        return isset($this->_singleValuedAssociationFields[$part]);
    }

    public function isPartCollectionValuedAssociationField($part)
    {
        return isset($this->_collectionValuedAssociationFields[$part]);
    }

    /* Setters to attach semantical information during semantical analysis. */

    public function setIsSimpleStateFieldPathExpression($bool)
    {
        $this->_isSimpleStateFieldPathExpression = $bool;
    }

    public function setIsSimpleStateFieldAssociationPathExpression($bool)
    {
        $this->_isSimpleStateFieldAssociationPathExpression = $bool;
    }

    public function setIsEmbeddedClassPart($part)
    {
        $this->_embeddedClassFields[$part] = true;
    }

    public function setIsSingleValuedAssociationPart($part)
    {
        $this->_singleValuedAssociationFields[$part] = true;
    }

    public function setIsCollectionValuedAssociationPart($part)
    {
        $this->_collectionValuedAssociationFields[$part] = true;
    }

    public function dispatch($sqlWalker)
    {
        return $sqlWalker->walkStateFieldPathExpression($this);
    }
}