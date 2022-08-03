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
 * Defines a Query Parameter.
 *
 * @link    www.doctrine-project.org
 */
class Parameter
{
    /**
     * Returns the internal representation of a parameter name.
     *
     * @param string|int $name The parameter name or position.
     *
     * @return string The normalized parameter name.
     */
    public static function normalizeName($name)
    {
        return trim((string) $name, ':');
    }

    /**
     * The parameter name.
     *
     * @var string
     */
    private $name;

    /**
     * The parameter value.
     *
     * @var mixed
     */
    private $value;

    /**
     * The parameter type.
     *
     * @var mixed
     */
    private $type;

    /**
     * Whether the parameter type was explicitly specified or not
     *
     * @var bool
     */
    private $typeSpecified;

    /**
     * @param string $name  Parameter name
     * @param mixed  $value Parameter value
     * @param mixed  $type  Parameter type
     */
    public function __construct($name, $value, $type = null)
    {
        $this->name          = self::normalizeName($name);
        $this->typeSpecified = $type !== null;

        $this->setValue($value, $type);
    }

    /**
     * Retrieves the Parameter name.
     *
     * @return string
     */
    public function getName()
    {
        return $this->name;
    }

    /**
     * Retrieves the Parameter value.
     *
     * @return mixed
     */
    public function getValue()
    {
        return $this->value;
    }

    /**
     * Retrieves the Parameter type.
     *
     * @return mixed
     */
    public function getType()
    {
        return $this->type;
    }

    /**
     * Defines the Parameter value.
     *
     * @param mixed $value Parameter value.
     * @param mixed $type  Parameter type.
     */
    public function setValue($value, $type = null)
    {
        $this->value = $value;
        $this->type  = $type ?: ParameterTypeInferer::inferType($value);
    }

    public function typeWasSpecified(): bool
    {
        return $this->typeSpecified;
    }
}
