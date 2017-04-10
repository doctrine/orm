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

use Doctrine\DBAL\Types\Type;
use Doctrine\ORM\Mapping\ColumnMetadata;

abstract class ColumnMetadataBuilder
{
    /** @var string */
    protected $tableName;

    /** @var string */
    protected $columnName;

    /** @var Type */
    protected $type;

    /** @var int */
    protected $length = 255;

    /** @var int */
    protected $scale;

    /** @var int */
    protected $precision;

    /** @var string */
    protected $columnDefinition;

    /** @var array */
    protected $options = [];

    /** @var bool */
    protected $primaryKey = false;

    /** @var bool */
    protected $nullable = false;

    /** @var bool */
    protected $unique = false;

    /**
     * ColumnMetadataBuilder constructor.
     *
     */
    public function __construct()
    {
        $this->type = Type::getType('string');
    }

    /**
     * @param string $columnName
     *
     * @return self
     */
    public function withColumnName(string $columnName)
    {
        $this->columnName = $columnName;

        return $this;
    }

    /**
     * @param Type $type
     *
     * @return self
     */
    public function withType(Type $type)
    {
        $this->type = $type;

        return $this;
    }

    /**
     * @param string $tableName
     *
     * @return self
     */
    public function withTableName(string $tableName)
    {
        $this->tableName = $tableName;

        return $this;
    }

    /**
     * @param string $columnDefinition
     *
     * @return self
     */
    public function withColumnDefinition(string $columnDefinition)
    {
        $this->columnDefinition = $columnDefinition;

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
     * @param bool $primaryKey
     *
     * @return self
     */
    public function withPrimaryKey(bool $primaryKey)
    {
        $this->primaryKey = $primaryKey;

        return $this;
    }

    /**
     * @param bool $nullable
     *
     * @return self
     */
    public function withNullable(bool $nullable)
    {
        $this->nullable = $nullable;

        return $this;
    }

    /**
     * @param bool $unique
     *
     * @return self
     */
    public function withUnique(bool $unique)
    {
        $this->unique = $unique;

        return $this;
    }

    /**
     * @return ColumnMetadata
     */
    public function build()
    {
        $columnMetadata = $this->createMetadataObject();

        if ($this->tableName !== null) {
            $columnMetadata->setTableName($this->tableName);
        }

        if ($this->columnDefinition !== null) {
            $columnMetadata->setColumnDefinition($this->columnDefinition);
        }

        // @todo guilhermeblanco Remove this once constructor arguments is in place
        $columnMetadata->setColumnName($this->columnName);
        $columnMetadata->setType($this->type);

        $columnMetadata->setOptions($this->options);
        $columnMetadata->setPrimaryKey($this->primaryKey);
        $columnMetadata->setNullable($this->nullable);
        $columnMetadata->setUnique($this->unique);

        return $columnMetadata;
    }

    /**
     * @return ColumnMetadata
     */
    abstract protected function createMetadataObject();
}