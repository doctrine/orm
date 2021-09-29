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

use function trim;

/**
 * Defines a Query Identifier.
 *
 * @link    www.doctrine-project.org
 */
class Identifier
{
    /**
     * Returns the internal representation of a identifier name.
     *
     * @param string|int $name The identifier name or position.
     *
     * @return string The normalized identifier name.
     */
    public static function normalizeName($name)
    {
        return trim((string) $name, '{}');
    }

    /**
     * The identifier name.
     *
     * @var string
     */
    private $name;

    /**
     * The identifier value.
     *
     * @var mixed
     */
    private $value;

    /**
     * @param string $name  Identifier name
     * @param mixed  $value Identifier value
     */
    public function __construct($name, $value)
    {
        $this->name = self::normalizeName($name);

        $this->setValue($value);
    }

    /**
     * Retrieves the Identifier name.
     *
     * @return string
     */
    public function getName()
    {
        return $this->name;
    }

    /**
     * Retrieves the Identifier value.
     *
     * @return mixed
     */
    public function getValue()
    {
        return $this->value;
    }

    /**
     * Defines the Identifier value.
     *
     * @param mixed $value Identifier value.
     *
     * @return void
     */
    public function setValue($value)
    {
        $this->value = $value;
    }
}
