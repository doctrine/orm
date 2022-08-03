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

use InvalidArgumentException;

use function count;
use function get_class;
use function implode;
use function in_array;
use function is_string;
use function sprintf;

/**
 * Abstract base Expr class for building DQL parts.
 *
 * @link    www.doctrine-project.org
 */
abstract class Base
{
    /** @var string */
    protected $preSeparator = '(';

    /** @var string */
    protected $separator = ', ';

    /** @var string */
    protected $postSeparator = ')';

    /** @psalm-var list<class-string> */
    protected $allowedClasses = [];

    /** @psalm-var list<string|object> */
    protected $parts = [];

    /**
     * @param mixed $args
     */
    public function __construct($args = [])
    {
        $this->addMultiple($args);
    }

    /**
     * @psalm-param list<string|object> $args
     *
     * @return static
     */
    public function addMultiple($args = [])
    {
        foreach ((array) $args as $arg) {
            $this->add($arg);
        }

        return $this;
    }

    /**
     * @param mixed $arg
     *
     * @return static
     *
     * @throws InvalidArgumentException
     */
    public function add($arg)
    {
        if ($arg !== null && (! $arg instanceof self || $arg->count() > 0)) {
            // If we decide to keep Expr\Base instances, we can use this check
            if (! is_string($arg)) {
                $class = get_class($arg);

                if (! in_array($class, $this->allowedClasses)) {
                    throw new InvalidArgumentException(sprintf(
                        "Expression of type '%s' not allowed in this context.",
                        $class
                    ));
                }
            }

            $this->parts[] = $arg;
        }

        return $this;
    }

    /**
     * @return int
     */
    public function count()
    {
        return count($this->parts);
    }

    /**
     * @return string
     */
    public function __toString()
    {
        if ($this->count() === 1) {
            return (string) $this->parts[0];
        }

        return $this->preSeparator . implode($this->separator, $this->parts) . $this->postSeparator;
    }
}
