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

namespace Doctrine\ORM\Query\Expr;

use function count;
use function implode;

/**
 * Expression class for building DQL Order By parts.
 *
 * @link    www.doctrine-project.org
 */
class OrderBy
{
    /** @var string */
    protected $preSeparator = '';

    /** @var string */
    protected $separator = ', ';

    /** @var string */
    protected $postSeparator = '';

    /** @var string[] */
    protected $allowedClasses = [];

    /** @psalm-var list<string> */
    protected $parts = [];

    /**
     * @param string|null $sort
     * @param string|null $order
     */
    public function __construct($sort = null, $order = null)
    {
        if ($sort) {
            $this->add($sort, $order);
        }
    }

    /**
     * @param string      $sort
     * @param string|null $order
     *
     * @return void
     */
    public function add($sort, $order = null)
    {
        $order         = ! $order ? 'ASC' : $order;
        $this->parts[] = $sort . ' ' . $order;
    }

    /**
     * @return int
     */
    public function count()
    {
        return count($this->parts);
    }

    /**
     * @psalm-return list<string>
     */
    public function getParts()
    {
        return $this->parts;
    }

    /**
     * @return string
     */
    public function __toString()
    {
        return $this->preSeparator . implode($this->separator, $this->parts) . $this->postSeparator;
    }
}
