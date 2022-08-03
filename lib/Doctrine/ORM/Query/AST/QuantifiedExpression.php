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

namespace Doctrine\ORM\Query\AST;

use function strtoupper;

/**
 * QuantifiedExpression ::= ("ALL" | "ANY" | "SOME") "(" Subselect ")"
 *
 * @link    www.doctrine-project.org
 */
class QuantifiedExpression extends Node
{
    /** @var string */
    public $type;

    /** @var Subselect */
    public $subselect;

    /**
     * @param Subselect $subselect
     */
    public function __construct($subselect)
    {
        $this->subselect = $subselect;
    }

    /**
     * @return bool
     */
    public function isAll()
    {
        return strtoupper($this->type) === 'ALL';
    }

    /**
     * @return bool
     */
    public function isAny()
    {
        return strtoupper($this->type) === 'ANY';
    }

    /**
     * @return bool
     */
    public function isSome()
    {
        return strtoupper($this->type) === 'SOME';
    }

    /**
     * {@inheritdoc}
     */
    public function dispatch($sqlWalker)
    {
        return $sqlWalker->walkQuantifiedExpression($this);
    }
}
