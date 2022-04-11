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

namespace Doctrine\ORM\Internal\Hydration;

use Iterator;
use ReturnTypeWillChange;

/**
 * Represents a result structure that can be iterated over, hydrating row-by-row
 * during the iteration. An IterableResult is obtained by AbstractHydrator#iterate().
 *
 * @deprecated
 */
class IterableResult implements Iterator
{
    /** @var AbstractHydrator */
    private $_hydrator;

    /** @var bool */
    private $_rewinded = false;

    /** @var int */
    private $_key = -1;

    /** @var mixed[]|null */
    private $_current = null;

    /**
     * @param AbstractHydrator $hydrator
     */
    public function __construct($hydrator)
    {
        $this->_hydrator = $hydrator;
    }

    /**
     * @return void
     *
     * @throws HydrationException
     */
    #[ReturnTypeWillChange]
    public function rewind()
    {
        if ($this->_rewinded === true) {
            throw new HydrationException('Can only iterate a Result once.');
        } else {
            $this->_current  = $this->next();
            $this->_rewinded = true;
        }
    }

    /**
     * Gets the next set of results.
     *
     * @return mixed[]|false
     */
    #[ReturnTypeWillChange]
    public function next()
    {
        $this->_current = $this->_hydrator->hydrateRow();
        $this->_key++;

        return $this->_current;
    }

    /**
     * @return mixed
     */
    #[ReturnTypeWillChange]
    public function current()
    {
        return $this->_current;
    }

    /**
     * @return int
     */
    #[ReturnTypeWillChange]
    public function key()
    {
        return $this->_key;
    }

    /**
     * @return bool
     */
    #[ReturnTypeWillChange]
    public function valid()
    {
        return $this->_current !== false;
    }
}
