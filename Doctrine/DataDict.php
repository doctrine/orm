<?php
class Doctrine_DataDict {

    private $dbh;

    public function __construct(PDO $dbh) {
        $file = Doctrine::getPath().DIRECTORY_SEPARATOR."Doctrine".DIRECTORY_SEPARATOR."adodb-hack".DIRECTORY_SEPARATOR."adodb.inc.php";

        if( ! file_exists($file))
            throw new Doctrine_Exception("Couldn't include datadict. File $file does not exist");

        require_once($file);

        $this->dbh  = $dbh;
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
                
            $r[] = $name." ".$this->getADOType($args[0],$args[1])." ".implode(' ',$args[2]);
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
                elseif($length <= 20)
                    return "I8";
                else
                    throw new Doctrine_Exception("Too long integer (max length is 20).");

            break;
            default:
                throw new Doctrine_Exception("Unknown column type $type");
        endswitch;
    }
    
    /**
     * Converts native database column type to doctrine data type     
     * 
     * @param string $column            column type
     * @param integer $length           column length
     * @param string $dbType            Database driver name as returned by PDO::getAttribute(PDO::ATTR_DRIVER_NAME)
     * @param string $dbVersion         Database server version as return by PDO::getAttribute(PDO::ATTR_SERVER_VERSION)
     * @return array of doctrine column type and column length. In future may also return a validator name.
     * @throws Doctrine_Exception on unknown column type
     * @author Jukka Hassinen <Jukka.Hassinen@BrainAlliance.com>
     */
    public static function getDoctrineType($colType,$colLength, $dbType = null, $dbVersion = null) 
    {
    	return array($colType, $colLength); /* @todo FIXME i am incomplete*/
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

