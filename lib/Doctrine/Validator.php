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
 * <http://www.phpdoctrine.com>.
 */
/**
 * Doctrine_Validator
 * Doctrine_Validator performs validations in record properties
 *
 * @package     Doctrine
 * @license     http://www.opensource.org/licenses/lgpl-license.php LGPL
 * @category    Object Relational Mapping
 * @link        www.phpdoctrine.com
 * @since       1.0
 * @version     $Revision$
 * @author      Konsta Vesterinen <kvesteri@cc.hut.fi>
 */
class Doctrine_Validator extends Doctrine_Object
{
    /**
     * @var array $validators           an array of validator objects
     */
    private static $validators  = array();
    /**
     * returns a validator object
     *
     * @param string $name
     * @return Doctrine_Validator_Interface
     */
    public static function getValidator($name)
    {
        if ( ! isset(self::$validators[$name])) {
            $class = 'Doctrine_Validator_' . ucwords(strtolower($name));
            if (class_exists($class)) {
                self::$validators[$name] = new $class;
            } else {
                throw new Doctrine_Exception("Validator named '$name' not available.");
            }

        }
        return self::$validators[$name];
    }
    /**
     * validates a given record and saves possible errors
     * in Doctrine_Validator::$stack
     *
     * @param Doctrine_Record $record
     * @return void
     */
    public function validateRecord(Doctrine_Record $record)
    {
        $columns   = $record->getTable()->getColumns();
        $component = $record->getTable()->getComponentName();

        $errorStack = $record->getErrorStack();

        // if record is transient all fields will be validated
        // if record is persistent only the modified fields will be validated
        $data = ($record->exists()) ? $record->getModified() : $record->getData();

        $err      = array();
        foreach ($data as $key => $value) {
            if ($value === self::$_null) {
                $value = null;
            } elseif ($value instanceof Doctrine_Record) {
                $value = $value->getIncremented();
            }

            $column = $columns[$key];

            if ($column['type'] == 'enum') {
                $value = $record->getTable()->enumIndex($key, $value);

                if ($value === false) {
                    $errorStack->add($key, 'enum');
                    continue;
                }
            }

            if ($record->getTable()->getAttribute(Doctrine::ATTR_VALIDATE) & Doctrine::VALIDATE_LENGTHS) {
                if (!$this->validateLength($column, $key, $value)) {
                    $errorStack->add($key, 'length');

                    continue;
                }
            }

            foreach ($column as $name => $args) {
                if (empty($name)
                    || $name == 'primary'
                    || $name == 'protected'
                    || $name == 'autoincrement'
                    || $name == 'default'
                    || $name == 'values'
                    || $name == 'sequence'
                    || $name == 'zerofill'
                    || $name == 'scale') {
                    continue;
                }

                if (strtolower($name) === 'notnull' && isset($column['autoincrement'])) {
                    continue;
                }

                if (strtolower($name) == 'length') {
                    if ( ! ($record->getTable()->getAttribute(Doctrine::ATTR_VALIDATE) & Doctrine::VALIDATE_LENGTHS)) {
                        if (!$this->validateLength($column, $key, $value)) {
                            $errorStack->add($key, 'length');
                        }
                    }
                    continue;
                }

                if (strtolower($name) == 'type') {
                    if ( ! ($record->getTable()->getAttribute(Doctrine::ATTR_VALIDATE) & Doctrine::VALIDATE_TYPES)) {
                        if ( ! self::isValidType($value, $column['type'])) {
                            $errorStack->add($key, 'type');
                        }
                    }
                    continue;
                }

                $validator = self::getValidator($name);
                $validator->invoker = $record;
                $validator->field   = $key;
                $validator->args    = $args;

                if ( ! $validator->validate($value)) {
                    $errorStack->add($key, $name);

                    //$err[$key] = 'not valid';

                    // errors found quit validation looping for this column
                    //break;
                }
            }

            if ($record->getTable()->getAttribute(Doctrine::ATTR_VALIDATE) & Doctrine::VALIDATE_TYPES) {
                if ( ! self::isValidType($value, $column['type'])) {
                    $errorStack->add($key, 'type');
                    continue;
                }
            }
        }
    }
    /**
     * Validates the length of a field.
     */
    private function validateLength($column, $key, $value)
    {
        if ($column['type'] == 'timestamp' || $column['type'] == 'integer') {
            return true;
        } elseif ($column['type'] == 'array' || $column['type'] == 'object') {
            $length = strlen(serialize($value));
        } else {
            $length = strlen($value);
        }

        if ($length > $column['length']) {
            return false;
        }
        return true;
    }
    /**
     * whether or not this validator has errors
     *
     * @return boolean
     */
    public function hasErrors()
    {
        return (count($this->stack) > 0);
    }
    /**
     * phpType
     * converts a doctrine type to native php type
     *
     * @param $portableType     portable doctrine type
     * @return string
     */
    public static function phpType($portableType)
    {
        switch ($portableType) {
            case 'enum':
                return 'integer';
            case 'blob':
            case 'clob':
            case 'mbstring':
            case 'timestamp':
            case 'date':
            case 'gzip':
                return 'string';
                break;
            default:
                return $portableType;
        }
    }
    /**
     * returns whether or not the given variable is
     * valid type
     *
     * @param mixed $var
     * @param string $type
     * @return boolean
     */
    public static function isValidType($var, $type)
    {
        if ($type == 'boolean') {
            return true;
        }

        $looseType = self::gettype($var);
        $type      = self::phpType($type);

        switch ($looseType) {
            case 'float':
            case 'double':
            case 'integer':
                if ($type == 'string' || $type == 'float') {
                    return true;
                }
            case 'string':
            case 'array':
            case 'object':
                return ($type === $looseType);
                break;
            case 'NULL':
                return true;
                break;
        }
    }
    /**
     * returns the type of loosely typed variable
     *
     * @param mixed $var
     * @return string
     */
    public static function gettype($var)
    {
        $type = gettype($var);
        switch ($type) {
            case 'string':
                if (preg_match("/^[0-9]+$/",$var)) {
                    return 'integer';
                } elseif (is_numeric($var)) {
                    return 'float';
                } else {
                    return $type;
                }
                break;
            default:
                return $type;
        }
    }
}
