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
 * Doctrine_Lib has not commonly used static functions, mostly for debugging purposes
 *
 * @package     Doctrine
 * @subpackage  Lib
 * @license     http://www.opensource.org/licenses/lgpl-license.php LGPL
 * @link        www.phpdoctrine.com
 * @since       1.0
 * @version     $Revision$
 * @author      Konsta Vesterinen <kvesteri@cc.hut.fi>
 */
class Doctrine_Lib
{
    /**
     * getRecordStateAsString
     *
     * @param integer $state the state of record
     * @see Doctrine_Record::STATE_* constants
     * @return string string representation of given state
     */
    public static function getRecordStateAsString($state)
    {
        switch ($state) {
            case Doctrine_Record::STATE_PROXY:
                return "proxy";
                break;
            case Doctrine_Record::STATE_CLEAN:
                return "persistent clean";
                break;
            case Doctrine_Record::STATE_DIRTY:
                return "persistent dirty";
                break;
            case Doctrine_Record::STATE_TDIRTY:
                return "transient dirty";
                break;
            case Doctrine_Record::STATE_TCLEAN:
                return "transient clean";
                break;
        }
    }

    /**
     * getRecordAsString
     *
     * returns a string representation of Doctrine_Record object
     *
     * @param Doctrine_Record $record
     * @return string
     */
    public static function getRecordAsString(Doctrine_Record $record)
    {
        $r[] = '<pre>';
        $r[] = 'Component  : ' . $record->getTable()->getComponentName();
        $r[] = 'ID         : ' . $record->obtainIdentifier();
        $r[] = 'References : ' . count($record->getReferences());
        $r[] = 'State      : ' . Doctrine_Lib::getRecordStateAsString($record->getState());
        $r[] = 'OID        : ' . $record->getOID();
        $r[] = 'data       : ' . Doctrine::dump($record->getData(), false);
        $r[] = '</pre>';

        return implode("\n",$r)."<br />";
    }

    /**
     * getConnectionStateAsString
     *
     * returns a given connection state as string
     *
     * @param integer $state State of the connection as a string
     */
    public static function getConnectionStateAsString($state)
    {
        switch ($state) {
            case Doctrine_Transaction::STATE_SLEEP:
                return "open";
                break;
            case Doctrine_Transaction::STATE_BUSY:
                return "busy";
                break;
            case Doctrine_Transaction::STATE_ACTIVE:
                return "active";
                break;
        }
    }

    /**
     * getConnectionAsString
     *
     * returns a string representation of Doctrine_Connection object
     *
     * @param Doctrine_Connection $connection
     * @return string
     */
    public static function getConnectionAsString(Doctrine_Connection $connection)
    {
        $r[] = '<pre>';
        $r[] = 'Doctrine_Connection object';
        $r[] = 'State               : ' . Doctrine_Lib::getConnectionStateAsString($connection->transaction->getState());
        $r[] = 'Open Transactions   : ' . $connection->transaction->getTransactionLevel();
        $r[] = 'Table in memory     : ' . $connection->count();
        $r[] = 'Driver name         : ' . $connection->getAttribute(Doctrine::ATTR_DRIVER_NAME);
        $r[] = "</pre>";
        
        return implode("\n",$r)."<br>";
    }

    /**
     * getTableAsString
     *
     * returns a string representation of Doctrine_Table object
     *
     * @param Doctrine_Table $table
     * @return string
     */
    public static function getTableAsString(Doctrine_Table $table)
    {
        $r[] = "<pre>";
        $r[] = "Component   : ".$table->getComponentName();
        $r[] = "Table       : ".$table->getTableName();
        $r[] = "</pre>";
        
        return implode("\n",$r)."<br>";
    }

    /**
     * formatSql 
     * 
     * @todo: What about creating a config varialbe for the color?
     * @param mixed $sql 
     * @return string the formated sql
     */
    public static function formatSql($sql)
    {
        $e = explode("\n",$sql);
        $color = "367FAC";
        $l = $sql;
        $l = str_replace("SELECT ", "<font color='$color'><b>SELECT </b></font><br \>  ",$l);
        $l = str_replace("FROM ", "<font color='$color'><b>FROM </b></font><br \>",$l);
        $l = str_replace(" LEFT JOIN ", "<br \><font color='$color'><b> LEFT JOIN </b></font>",$l);
        $l = str_replace(" INNER JOIN ", "<br \><font color='$color'><b> INNER JOIN </b></font>",$l);
        $l = str_replace(" WHERE ", "<br \><font color='$color'><b> WHERE </b></font>",$l);
        $l = str_replace(" GROUP BY ", "<br \><font color='$color'><b> GROUP BY </b></font>",$l);
        $l = str_replace(" HAVING ", "<br \><font color='$color'><b> HAVING </b></font>",$l);
        $l = str_replace(" AS ", "<font color='$color'><b> AS </b></font><br \>  ",$l);
        $l = str_replace(" ON ", "<font color='$color'><b> ON </b></font>",$l);
        $l = str_replace(" ORDER BY ", "<font color='$color'><b> ORDER BY </b></font><br \>",$l);
        $l = str_replace(" LIMIT ", "<font color='$color'><b> LIMIT </b></font><br \>",$l);
        $l = str_replace(" OFFSET ", "<font color='$color'><b> OFFSET </b></font><br \>",$l);
        $l = str_replace("  ", "<dd>",$l);

        return $l;
    }

    /**
     * getCollectionAsString
     *
     * returns a string representation of Doctrine_Collection object
     *
     * @param Doctrine_Collection $collection
     * @return string
     */
    public static function getCollectionAsString(Doctrine_Collection $collection)
    {
        $r[] = "<pre>";
        $r[] = get_class($collection);
        $r[] = 'data : ' . Doctrine::dump($collection->getData(), false);
        //$r[] = 'snapshot : ' . Doctrine::dump($collection->getSnapshot());
        $r[] = "</pre>";
        
        return implode("\n",$r);
    }

    // Code from symfony sfToolkit class. See LICENSE
    // code from php at moechofe dot com (array_merge comment on php.net)
    /*
     * arrayDeepMerge
     *
     * array arrayDeepMerge ( array array1 [, array array2 [, array ...]] )
     *
     * Like array_merge
     *
     *  arrayDeepMerge() merges the elements of one or more arrays together so
     * that the values of one are appended to the end of the previous one. It
     * returns the resulting array.
     *  If the input arrays have the same string keys, then the later value for
     * that key will overwrite the previous one. If, however, the arrays contain
     * numeric keys, the later value will not overwrite the original value, but
     * will be appended.
     *  If only one array is given and the array is numerically indexed, the keys
     * get reindexed in a continuous way.
     *
     * Different from array_merge
     *  If string keys have arrays for values, these arrays will merge recursively.
     */
     public static function arrayDeepMerge()
     {
         switch (func_num_args()) {
             case 0:
                return false;
             case 1:
                return func_get_arg(0);
             case 2:
                $args = func_get_args();
                $args[2] = array();
                
                if (is_array($args[0]) && is_array($args[1]))
                {
                    foreach (array_unique(array_merge(array_keys($args[0]),array_keys($args[1]))) as $key)
                    {
                        $isKey0 = array_key_exists($key, $args[0]);
                        $isKey1 = array_key_exists($key, $args[1]);

                        if ($isKey0 && $isKey1 && is_array($args[0][$key]) && is_array($args[1][$key]))
                        {
                            $args[2][$key] = self::arrayDeepMerge($args[0][$key], $args[1][$key]);
                        } else if ($isKey0 && $isKey1) {
                            $args[2][$key] = $args[1][$key];
                        } else if ( ! $isKey1) {
                            $args[2][$key] = $args[0][$key];
                        } else if ( ! $isKey0) {
                            $args[2][$key] = $args[1][$key];
                        }
                    }

                    return $args[2];
                } else {
                    return $args[1];
                }
            default:
                $args = func_get_args();
                $args[1] = sfToolkit::arrayDeepMerge($args[0], $args[1]);
                array_shift($args);

                return call_user_func_array(array('Doctrine', 'arrayDeepMerge'), $args);
            break;
        }
    }

    // Code from symfony sfToolkit class. See LICENSE
    /**
     * stringToArray
     *
     * @param string $string 
     * @return void
     */
    public static function stringToArray($string)
    {
        preg_match_all('/
          \s*(\w+)              # key                               \\1
          \s*=\s*               # =
          (\'|")?               # values may be included in \' or " \\2
          (.*?)                 # value                             \\3
          (?(2) \\2)            # matching \' or " if needed        \\4
          \s*(?:
            (?=\w+\s*=) | \s*$  # followed by another key= or the end of the string
          )
        /x', $string, $matches, PREG_SET_ORDER);

        $attributes = array();
        foreach ($matches as $val) {
            $attributes[$val[1]] = self::literalize($val[3]);
        }

        return $attributes;
    }

    /**
     * Finds the type of the passed value, returns the value as the new type.
     *
     * @param  string
     * @return mixed
     */
    public static function literalize($value, $quoted = false)
    {
        // lowercase our value for comparison
        $value  = trim($value);
        $lvalue = strtolower($value);

        if (in_array($lvalue, array('null', '~', '')))
        {
            $value = null;
        } else if (in_array($lvalue, array('true', 'on', '+', 'yes'))) {
            $value = true;
        } else if (in_array($lvalue, array('false', 'off', '-', 'no'))) {
            $value = false;
        } else if (ctype_digit($value)) {
            $value = (int) $value;
        } else if (is_numeric($value)) {
            $value = (float) $value;
        } else {
            if ($quoted)
            {
                $value = '\''.str_replace('\'', '\\\'', $value).'\'';
            }
        }

        return $value;
    }

    /**
     * getValidators
     *
     * Get available doctrine validators
     *
     * @return array $validators
     */
    public static function getValidators()
    {
        $validators = array();

        $dir = Doctrine::getPath() . DIRECTORY_SEPARATOR . 'Doctrine' . DIRECTORY_SEPARATOR . 'Validator';

        $files = new RecursiveIteratorIterator(new RecursiveDirectoryIterator($dir), RecursiveIteratorIterator::LEAVES_ONLY);
        foreach ($files as $file) {
            $e = explode('.', $file->getFileName());

            if (end($e) == 'php') {
                $name = strtolower($e[0]);

                $validators[$name] = $name;
            }
        }

        return $validators;
    }

    /**
     * makeDirectories
     *
     * Makes the directories for a path recursively
     *
     * @param string $path
     * @return void
     */
    public static function makeDirectories($path, $mode = 0777)
    {
        if ( ! $path) {
          return false;
        }

        if (is_dir($path) || is_file($path)) {
          return true;
        }

        return mkdir($path, $mode, true);
    }

    /**
     * removeDirectories
     *
     * @param string $folderPath
     * @return void
     */
    public static function removeDirectories($folderPath)
    {
        if (is_dir($folderPath))
        {
            foreach (scandir($folderPath) as $value)
            {
                if ($value != '.' && $value != '..')
                {
                    $value = $folderPath . "/" . $value;

                    if (is_dir($value)) {
                        self::removeDirectories($value);
                    } else if (is_file($value)) {
                        @unlink($value);
                    }
                }
            }

            return rmdir ( $folderPath );
        } else {
            return false;
        }
    }

    /**
     * isValidClassName
     *
     * checks for valid class name (uses camel case and underscores)
     *
     * @param string $classname
     * @return boolean
     */
    public static function isValidClassName($className)
    {
        if (preg_match('~(^[a-z])|(_[a-z])|([\W])|(_{2})~', $className)) {
            return false;
        }

        return true;
    }
}