<?php
/*
 *  $Id$
 *
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
 * and is licensed under the LGPL. For more information, see
 * <http://www.doctrine-project.org>.
 */

namespace Doctrine\DBAL\Schema;

use \Doctrine\DBAL\Types\Type;
use Doctrine\DBAL\Schema\Visitor\Visitor;

/**
 * Object representation of a database column
 *
 * @license http://www.opensource.org/licenses/lgpl-license.php LGPL
 * @link    www.doctrine-project.org
 * @since   2.0
 * @version $Revision$
 * @author  Benjamin Eberlei <kontakt@beberlei.de>
 */
class Column extends AbstractAsset
{
    /**
     * @var \Doctrine\DBAL\Types\Type
     */
    protected $_type;

    /**
     * @var int
     */
    protected $_length = 255;

    /**
     * @var int
     */
    protected $_precision = 0;

    /**
     * @var int
     */
    protected $_scale = 0;

    /**
     * @var bool
     */
    protected $_unsigned = false;

    /**
     * @var bool
     */
    protected $_fixed = false;

    /**
     * @var bool
     */
    protected $_notnull = true;

    /**
     * @var string
     */
    protected $_default = null;

    /**
     * @var array
     */
    protected $_platformOptions = array();

    /**
     * @var string
     */
    protected $_columnDefinition = null;

    /**
     * Create a new Column
     * 
     * @param string $columnName
     * @param Doctrine\DBAL\Types\Type $type
     * @param int $length
     * @param bool $notNull
     * @param mixed $default
     * @param bool $unsigned
     * @param bool $fixed
     * @param int $precision
     * @param int $scale
     * @param array $platformOptions
     */
    public function __construct($columnName, Type $type, array $options=array())
    {
        $this->_setName($columnName);
        $this->setType($type);
        $this->setOptions($options);
    }

    /**
     * @param array $options
     * @return Column
     */
    public function setOptions(array $options)
    {
        foreach ($options AS $name => $value) {
            $method = "set".$name;
            if (method_exists($this, $method)) {
                $this->$method($value);
            }
        }
        return $this;
    }

    /**
     * @param Type $type
     * @return Column
     */
    public function setType(Type $type)
    {
        $this->_type = $type;
        return $this;
    }

    /**
     * @param int $length
     * @return Column
     */
    public function setLength($length)
    {
        if($length !== null) {
            $this->_length = (int)$length;
        } else {
            $this->_length = null;
        }
        return $this;
    }

    /**
     * @param int $precision
     * @return Column
     */
    public function setPrecision($precision)
    {
        $this->_precision = (int)$precision;
        return $this;
    }

    /**
     * @param  int $scale
     * @return Column
     */
    public function setScale($scale)
    {
        $this->_scale = $scale;
        return $this;
    }

    /**
     *
     * @param  bool $unsigned
     * @return Column
     */
    public function setUnsigned($unsigned)
    {
        $this->_unsigned = (bool)$unsigned;
        return $this;
    }

    /**
     *
     * @param  bool $fixed
     * @return Column
     */
    public function setFixed($fixed)
    {
        $this->_fixed = (bool)$fixed;
        return $this;
    }

    /**
     * @param  bool $notnull
     * @return Column
     */
    public function setNotnull($notnull)
    {
        $this->_notnull = (bool)$notnull;
        return $this;
    }

    /**
     *
     * @param  mixed $default
     * @return Column
     */
    public function setDefault($default)
    {
        $this->_default = $default;
        return $this;
    }

    /**
     *
     * @param array $platformOptions
     * @return Column
     */
    public function setPlatformOptions(array $platformOptions)
    {
        $this->_platformOptions = $platformOptions;
        return $this;
    }

    /**
     *
     * @param  string $name
     * @param  mixed $value
     * @return Column
     */
    public function setPlatformOption($name, $value)
    {
        $this->_platformOptions[$name] = $value;
        return $this;
    }

    /**
     *
     * @param  string
     * @return Column
     */
    public function setColumnDefinition($value)
    {
        $this->_columnDefinition = $value;
        return $this;
    }

    public function getType()
    {
        return $this->_type;
    }

    public function getLength()
    {
        return $this->_length;
    }

    public function getPrecision()
    {
        return $this->_precision;
    }

    public function getScale()
    {
        return $this->_scale;
    }

    public function getUnsigned()
    {
        return $this->_unsigned;
    }

    public function getFixed()
    {
        return $this->_fixed;
    }

    public function getNotnull()
    {
        return $this->_notnull;
    }

    public function getDefault()
    {
        return $this->_default;
    }

    public function getPlatformOptions()
    {
        return $this->_platformOptions;
    }

    public function hasPlatformOption($name)
    {
        return isset($this->_platformOptions[$name]);
    }

    public function getPlatformOption($name)
    {
        return $this->_platformOptions[$name];
    }

    public function getColumnDefinition()
    {
        return $this->_columnDefinition;
    }

    /**
     * @param Visitor $visitor
     */
    public function visit(\Doctrine\DBAL\Schema\Visitor $visitor)
    {
        $visitor->accept($this);
    }

    /**
     * @return array
     */
    public function toArray()
    {
        return array_merge(array(
            'name'          => $this->_name,
            'type'          => $this->_type,
            'default'       => $this->_default,
            'notnull'       => $this->_notnull,
            'length'        => $this->_length,
            'precision'     => $this->_precision,
            'scale'         => $this->_scale,
            'fixed'         => $this->_fixed,
            'unsigned'      => $this->_unsigned,
            'columnDefinition' => $this->_columnDefinition,
        ), $this->_platformOptions);
    }
}