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

namespace Doctrine\ORM\Mapping\Builder;

use Doctrine\ORM\Mapping\DefaultNamingStrategy;
use Doctrine\ORM\Mapping\FieldMetadata;
use Doctrine\ORM\Mapping\NamingStrategy;
use Doctrine\ORM\Mapping\TableMetadata;

class TableMetadataBuilder implements Builder
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
     * @param string $name
     *
     * @return self
     */
    public function withName(string $name)
    {
        $this->name = $name;

        return $this;
    }

    /**
     * @param string $schema
     *
     * @return self
     */
    public function withSchema(string $schema)
    {
        $this->schema = $schema;

        return $this;
    }

    /**
     * @param array $options
     *
     * @return self
     */
    public function withOptions(array $options)
    {
        $this->options = $options;

        return $this;
    }

    /**
     * @param string $name
     * @param mixed  $value
     *
     * @return self
     */
    public function withOption(string $name, $value)
    {
        $this->options[$name] = $value;

        return $this;
    }

    /**
     * @param string|null $name
     * @param array       $columns
     * @param bool        $unique
     * @param array       $options
     * @param array       $flags
     *
     * @return self
     */
    public function withIndex($name, array $columns, bool $unique = false, array $options = [], array $flags = [])
    {
        $this->indexes[] = [
            'name'    => $name,
            'columns' => $columns,
            'unique'  => $unique,
            'options' => $options,
            'flags'   => $flags,
        ];

        return $this;
    }

    /**
     * @param string|null $name
     * @param array       $columns
     * @param array       $options
     * @param array       $flags
     *
     * @return self
     */
    public function withUniqueConstraint($name, array $columns, array $options = [], array $flags = [])
    {
        $this->uniqueConstraints[] = [
            'name'    => $name,
            'columns' => $columns,
            'options' => $options,
            'flags'   => $flags,
        ];

        return $this;
    }

    /**
     * @return TableMetadata
     */
    public function build()
    {
        $tableMetadata = $this->createMetadataObject();

        if ($this->name !== null) {
            $tableMetadata->setName($this->name);
        }

        if ($this->schema !== null) {
            $tableMetadata->setSchema($this->schema);
        }

        $tableMetadata->setOptions($this->options);

        foreach ($this->indexes as $index) {
            $tableMetadata->addIndex($index);
        }

        foreach ($this->uniqueConstraints as $constraint) {
            $tableMetadata->addUniqueConstraint($constraint);
        }

        return $tableMetadata;
    }

    /**
     * @return TableMetadata
     */
    protected function createMetadataObject()
    {
        return new TableMetadata();
    }
}