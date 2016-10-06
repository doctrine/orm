<?php

declare(strict_types = 1);

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

namespace Doctrine\ORM\Mapping;

use Doctrine\DBAL\Platforms\AbstractPlatform;

class TableMetadata
{
    /** @var string */
    protected $schema;

    /** @var string */
    protected $name;

    /** @var array */
    protected $options = [];

    /** @var array */
    protected $indexes = [];

    /** @var array */
    protected $uniqueConstraints = [];

    /**
     * @return string
     */
    public function getSchema()
    {
        return $this->schema;
    }

    /**
     * @param string $schema
     */
    public function setSchema(string $schema)
    {
        $this->schema = $schema;
    }

    /**
     * @param string $name
     */
    public function setName(string $name)
    {
        $this->name = $name;
    }

    /**
     * @return string
     */
    public function getName()
    {
        return $this->name;
    }

    /**
     * @param AbstractPlatform $platform
     *
     * @return string
     */
    public function getQuotedQualifiedName(AbstractPlatform $platform)
    {
        if (!$this->schema) {
            return $platform->quoteIdentifier($this->name);
        }

        $separator = ( ! $platform->supportsSchemas() && $platform->canEmulateSchemas()) ? '__' : '.';

        return $platform->quoteIdentifier(sprintf('%s%s%s', $this->schema, $separator, $this->name));
    }

    /**
     * @return array
     */
    public function getOptions()
    {
        return $this->options;
    }

    /**
     * @param array $options
     */
    public function setOptions(array $options)
    {
        $this->options = $options;
    }

    /**
     * @param string $name
     *
     * @return mixed
     */
    public function getOption(string $name)
    {
        return $this->options[$name];
    }

    /**
     * @param string $name
     *
     * @return bool
     */
    public function hasOption(string $name)
    {
        return isset($this->options[$name]);
    }

    /**
     * @param string $name
     * @param mixed  $value
     */
    public function addOption(string $name, $value)
    {
        $this->options[$name] = $value;
    }

    /**
     * @return array
     */
    public function getIndexes()
    {
        return $this->indexes;
    }

    /**
     * @param string $name
     *
     * @return array
     */
    public function getIndex(string $name)
    {
        return $this->indexes[$name];
    }

    /**
     * @param string $name
     *
     * @return bool
     */
    public function hasIndex(string $name)
    {
        return isset($this->indexes[$name]);
    }

    /**
     * @param array $index
     */
    public function addIndex(array $index)
    {
        if (! isset($index['name'])) {
            $this->indexes[] = $index;

            return;
        }

        $this->indexes[$index['name']] = $index;
    }

    /**
     * @return array
     */
    public function getUniqueConstraints()
    {
        return $this->uniqueConstraints;
    }

    /**
     * @param string $name
     *
     * @return array
     */
    public function getUniqueConstraint(string $name)
    {
        return $this->uniqueConstraints[$name];
    }

    /**
     * @param string $name
     *
     * @return bool
     */
    public function hasUniqueConstraint(string $name)
    {
        return isset($this->uniqueConstraints[$name]);
    }

    /**
     * @param array $constraint
     */
    public function addUniqueConstraint(array $constraint)
    {
        if (! isset($constraint['name'])) {
            $this->uniqueConstraints[] = $constraint;

            return;
        }

        $this->uniqueConstraints[$constraint['name']] = $constraint;
    }
}