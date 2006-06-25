<?php
class Doctrine_DataDict {

    private $dbh;

    public function __construct(PDO $dbh) {
        $manager = Doctrine_Manager::getInstance();
        require_once($manager->getRoot()."/adodb-hack/adodb.inc.php");

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
            $r[] = $name." ".$this->getADOType($args[0],$args[1])." ".str_replace("|"," ",$args[2]);
        }


        $r = implode(", ",$r);
        $a = $this->dict->createTableSQL($tablename,$r);

        $return = true;
        foreach($a as $sql) {
            try {
                $this->dbh->query($sql);
            } catch(PDOException $e) {
                $return = false;
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
            case "a":
            case "object":
            case "o":
            case "string":
            case "s":
                if($length < 255)
                    return "C($length)";
                elseif($length < 4000) 
                    return "X";
                else
                    return "X2";
            break;
            case "mbstring":
                if($length < 255) 
                    return "C2($length)";
                
                return "X2";
            case "clob":
                return "XL";
            break;
            case "d":
            case "date":
                return "D";
            break;
            case "float":
            case "f":
            case "double":
                return "F";
            break;
            case "timestamp":
            case "t":
                return "T";
            break;
            case "boolean":
            case "bool":
                return "L";
            break;
            case "enum":
            case "e":
            case "integer":
            case "int":
            case "i":
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
        endswitch;
    }
}
?>
