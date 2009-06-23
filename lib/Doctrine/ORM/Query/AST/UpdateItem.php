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
 * UpdateItem ::= [IdentificationVariable "."] {StateField | SingleValuedAssociationField} "=" NewValue
 * NewValue ::= SimpleArithmeticExpression | StringPrimary | DatetimePrimary | BooleanPrimary |
 *              EnumPrimary | SimpleEntityExpression | "NULL"
 *
 * @author robo
 */
class UpdateItem extends Node
{
    private $_identificationVariable;
    private $_field;
    private $_newValue;

    public function __construct($field, $newValue)
    {
        $this->_field = $field;
        $this->_newValue = $newValue;
    }

    public function setIdentificationVariable($identVar)
    {
        $this->_identificationVariable = $identVar;
    }

    public function getIdentificationVariable()
    {
        return $this->_identificationVariable;
    }

    public function getField()
    {
        return $this->_field;
    }

    public function getNewValue()
    {
        return $this->_newValue;
    }

    public function dispatch($sqlWalker)
    {
        return $sqlWalker->walkUpdateItem($this);
    }
}

