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
    protected $length = 255;

    /**
     * @var integer
     */
    protected $scale = 0;

    /**
     * @var integer
     */
    protected $precision = 0;

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
    public function setColumnDefinition($columnDefinition)
    {
        $this->columnDefinition = $columnDefinition;
    }

    /**
     * @return integer
     */
    public function getLength()
    {
        return $this->length;
    }

    /**
     * @param integer $length
     */
    public function setLength($length)
    {
        $this->length = $length;
    }

    /**
     * @return integer
     */
    public function getScale()
    {
        return $this->scale;
    }

    /**
     * @param integer $scale
     */
    public function setScale($scale)
    {
        $this->scale = $scale;
    }

    /**
     * @return integer
     */
    public function getPrecision()
    {
        return $this->precision;
    }

    /**
     * @param integer $precision
     */
    public function setPrecision($precision)
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
     * @param boolean $isPrimaryKey
     */
    public function setPrimaryKey($isPrimaryKey)
    {
        $this->primaryKey = $isPrimaryKey;
    }

    /**
     * @return boolean
     */
    public function isPrimaryKey()
    {
        return $this->primaryKey;
    }

    /**
     * @param boolean $isNullable
     */
    public function setNullable($isNullable)
    {
        $this->nullable = $isNullable;
    }

    /**
     * @return boolean
     */
    public function isNullable()
    {
        return $this->nullable;
    }

    /**
     * @param boolean $isUnique
     */
    public function setUnique($isUnique)
    {
        $this->unique = $isUnique;
    }

    /**
     * @return boolean
     */
    public function isUnique()
    {
        return $this->unique;
    }
}