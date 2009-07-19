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
 * Description of AggregateExpression
 *
 * @author robo
 */
class AggregateExpression extends Node
{
    private $_functionName;
    private $_pathExpression;
    private $_isDistinct = false; // Some aggregate expressions support distinct, eg COUNT

    public function __construct($functionName, $pathExpression, $isDistinct)
    {
        $this->_functionName = $functionName;
        $this->_pathExpression = $pathExpression;
        $this->_isDistinct = $isDistinct;
    }

    public function getPathExpression()
    {
        return $this->_pathExpression;
    }

    public function isDistinct()
    {
        return $this->_isDistinct;
    }

    public function getFunctionName()
    {
        return $this->_functionName;
    }

    public function dispatch($sqlWalker)
    {
        return $sqlWalker->walkAggregateExpression($this);
    }
}