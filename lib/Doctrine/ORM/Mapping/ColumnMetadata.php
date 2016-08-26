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

namespace Doctrine\ORM\Mapping;

use Doctrine\DBAL\Types\Type;

class ColumnMetadata
{
    /**
     * @var string
     */
    protected $tableName;

    /**
     * @var string
     */
    protected $columnName;

    /**
     * @var Type
     */
    protected $type;

    /**
     * @var string
     */
    protected $columnDefinition;

    /**
     * @var integer
     */
    protected $length;

    /**
     * @var integer
     */
    protected $scale;

    /**
     * @var integer
     */
    protected $precision;

    /**
     * @var array
     */
    protected $options = [];

    /**
     * @var boolean
     */
    protected $primaryKey = false;

    /**
     * @var boolean
     */
    protected $nullable = false;

    /**
     * @var boolean
     */
    protected $unique = false;

    /**
     * ColumnMetadata constructor.
     *
     * @param string $columnName
     * @param Type   $type
     *
     * @todo Leverage this implementation instead of default, blank constructor
     */
    /*public function __construct(string $columnName, Type $type)
    {
        $this->columnName = $columnName;
        $this->type       = $type;
    }*/

    /**
     * @return string
     */
    public function getTableName()
    {
        return $this->tableName;
    }

    /**
     * @param string $tableName
     */
    public function setTableName($tableName)
    {
        $this->tableName = $tableName;
    }

    /**
     * @return string
     */
    public function getColumnName()
    {
        return $this->columnName;
    }

    /**
     * @param string $columnName
     */
    public function setColumnName($columnName)
    {
        $this->columnName = $columnName;
    }

    /**
     * @return Type
     */
    public function getType()
    {
        return $this->type;
    }

    /**
     * @param Type $type
     */
    public function setType(Type $type)
    {
        $this->type = $type;
    }

    /**
     * @return string
     */
    public function getTypeName()
    {
        return $this->type->getName();
    }

    /**
     * @return string
     */
    public function getColumnDefinition()
    {
        return $this->columnDefinition;
    }

    /**
     * @param string $columnDefinition
     */
    public function setColumnDefinition(string $columnDefinition)
    {
        $this->columnDefinition = $columnDefinition;
    }

    /**
     * @return int
     */
    public function getLength()
    {
        return $this->length;
    }

    /**
     * @param int $length
     */
    public function setLength(int $length)
    {
        $this->length = $length;
    }

    /**
     * @return int
     */
    public function getScale()
    {
        return $this->scale;
    }

    /**
     * @param int $scale
     */
    public function setScale(int $scale)
    {
        $this->scale = $scale;
    }

    /**
     * @return int
     */
    public function getPrecision()
    {
        return $this->precision;
    }

    /**
     * @param int $precision
     */
    public function setPrecision(int $precision)
    {
        $this->precision = $precision;
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
     * @param bool $isPrimaryKey
     */
    public function setPrimaryKey(bool $isPrimaryKey)
    {
        $this->primaryKey = $isPrimaryKey;
    }

    /**
     * @return bool
     */
    public function isPrimaryKey()
    {
        return $this->primaryKey;
    }

    /**
     * @param bool $isNullable
     */
    public function setNullable(bool $isNullable)
    {
        $this->nullable = $isNullable;
    }

    /**
     * @return bool
     */
    public function isNullable()
    {
        return $this->nullable;
    }

    /**
     * @param bool $isUnique
     */
    public function setUnique(bool $isUnique)
    {
        $this->unique = $isUnique;
    }

    /**
     * @return bool
     */
    public function isUnique()
    {
        return $this->unique;
    }
}