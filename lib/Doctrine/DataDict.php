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
 * Doctrine_DataDict
 *
 * @package     Doctrine
 * @license     http://www.opensource.org/licenses/lgpl-license.php LGPL
 * @category    Object Relational Mapping
 * @link        www.phpdoctrine.com
 * @since       1.0
 * @version     $Revision$
 * @author      Konsta Vesterinen <kvesteri@cc.hut.fi>
 */
class Doctrine_DataDict {

    protected $dbh;

    public function __construct($dbh = null) {

        $file = Doctrine::getPath().DIRECTORY_SEPARATOR."Doctrine".DIRECTORY_SEPARATOR."adodb-hack".DIRECTORY_SEPARATOR."adodb.inc.php";

        if( ! file_exists($file))
            throw new Doctrine_Exception("Couldn't include datadict. File $file does not exist");

        require_once($file);

        $this->dbh  = $dbh;
        if($dbh)
        $this->dict = NewDataDictionary($dbh);
    }
    /**
     * metaColumns
     *
     * @param Doctrine_Table $table
     * @return array
     */
    public function metaColumns(Doctrine_Table $table) {
        return $this->dict->metaColumns($table->getTableName());
    }
    /**
     * createTable
     *
     * @param string $tablename
     * @param array $columns
     * @return boolean
     */
    public function createTable($tablename, array $columns) {
        foreach($columns as $name => $args) {
            if( ! is_array($args[2]))
                $args[2] = array();

            unset($args[2]['default']);

            $constraints = array_keys($args[2]);

            $r[] = $name." ".$this->getADOType($args[0],$args[1])." ".implode(' ', $constraints);
        }


        $r = implode(", ",$r);
        $a = $this->dict->createTableSQL($tablename,$r);

        $return = true;
        foreach($a as $sql) {
            try {
                $this->dbh->query($sql);
            } catch(Exception $e) {
                $return = $e;
            }
        }

        return $return;
    }
    /**
     * converts doctrine type to adodb type
     *
     * @param string $type              column type
     * @param integer $length           column length
     */
    public function getADOType($type,$length) {
        switch($type):
            case "array":
            case "object":
            case "string":
            case "gzip":
                if($length <= 255)
                    return "C($length)";
                elseif($length <= 4000)
                    return "X";
                else
                    return "X2";
            break;
            case "mbstring":
                if($length <= 255)
                    return "C2($length)";

                return "X2";
            case "clob":
                return "XL";
            break;
            case "blob":
                return "B";
            break;
            case "date":
                return "D";
            break;
            case "float":
            case "double":
                return "F";
            break;
            case "timestamp":
                return "T";
            break;
            case "boolean":
                return "L";
            break;
            case "enum":
            case "integer":
                if(empty($length))
                    return "I8";
                elseif($length < 4)
                    return "I1";
                elseif($length < 6)
                    return "I2";
                elseif($length < 10)
                    return "I4";
                else
                    return "I8";
            break;
            default:
                throw new Doctrine_Exception("Unknown column type $type");
        endswitch;
    }

    /**
     * checks for valid class name (uses camel case and underscores)
     *
     * @param string $classname
     * @return boolean
     */
    public static function isValidClassname($classname) {
        if(preg_match('~(^[a-z])|(_[a-z])|([\W])|(_{2})~', $classname))
            throw new Doctrine_Exception("Class name is not valid. Use camel case and underscores (i.e My_PerfectClass).");
        return true;
    }
}

