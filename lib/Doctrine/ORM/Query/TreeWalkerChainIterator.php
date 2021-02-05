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

namespace Doctrine\ORM\Query;

use ArrayAccess;
use Doctrine\ORM\AbstractQuery;
use Iterator;

use function key;
use function next;
use function reset;

/**
 * @template-implements Iterator<TreeWalker>
 * @template-implements ArrayAccess<int, TreeWalker>
 */
class TreeWalkerChainIterator implements Iterator, ArrayAccess
{
    /** @var class-string<TreeWalker>[] */
    private $walkers = [];
    /** @var TreeWalkerChain */
    private $treeWalkerChain;
    /** @var AbstractQuery */
    private $query;
    /** @var ParserResult */
    private $parserResult;

    /**
     * @param AbstractQuery $query
     * @param ParserResult  $parserResult
     */
    public function __construct(TreeWalkerChain $treeWalkerChain, $query, $parserResult)
    {
        $this->treeWalkerChain = $treeWalkerChain;
        $this->query           = $query;
        $this->parserResult    = $parserResult;
    }

    /**
     * @return string|false
     *
     * @psalm-return class-string<TreeWalker>|false
     */
    public function rewind()
    {
        return reset($this->walkers);
    }

    /**
     * @return TreeWalker|null
     */
    public function current()
    {
        return $this->offsetGet(key($this->walkers));
    }

    /**
     * @return int
     */
    public function key()
    {
        return key($this->walkers);
    }

    /**
     * @return TreeWalker|null
     */
    public function next()
    {
        next($this->walkers);

        return $this->offsetGet(key($this->walkers));
    }

    /**
     * {@inheritdoc}
     */
    public function valid()
    {
        return key($this->walkers) !== null;
    }

    /**
     * {@inheritdoc}
     */
    public function offsetExists($offset)
    {
        return isset($this->walkers[$offset]);
    }

    /**
     * @return TreeWalker|null
     */
    public function offsetGet($offset)
    {
        if ($this->offsetExists($offset)) {
            return new $this->walkers[$offset](
                $this->query,
                $this->parserResult,
                $this->treeWalkerChain->getQueryComponents()
            );
        }

        return null;
    }

    /**
     * {@inheritdoc}
     */
    public function offsetSet($offset, $value)
    {
        if ($offset === null) {
            $this->walkers[] = $value;
        } else {
            $this->walkers[$offset] = $value;
        }
    }

    /**
     * {@inheritdoc}
     */
    public function offsetUnset($offset)
    {
        if ($this->offsetExists($offset)) {
            unset($this->walkers[$offset]);
        }
    }
}
