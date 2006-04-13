<?php
class Doctrine_DataDict {
    private $table;
    public function __construct(Doctrine_Table $table) {
        $this->table = $table;
        $manager = $this->table->getSession()->getManager();

        require_once($manager->getRoot()."/adodb-hack/adodb.inc.php");

        $dbh  = $this->table->getSession()->getDBH();
        
        $this->dict = NewDataDictionary($dbh);
    }

    public function metaColumns() {
        return $this->dict->metaColumns($this->table->getTableName());
    }

    public function createTable() {      
        foreach($this->table->getColumns() as $name => $args) {

            $r[] = $name." ".$this->getADOType($args[0],$args[1])." ".$args[2];
        }
        $dbh  = $this->table->getSession()->getDBH();

        $r = implode(", ",$r);
        $a = $this->dict->createTableSQL($this->table->getTableName(),$r);

        $return = true;
        foreach($a as $sql) {
            try {
                $dbh->query($sql);
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
            case "string":
            case "s":
                if($length < 255)
                    return "C($length)";
                elseif($length < 4000) 
                    return "X";
            break;
            case "mbstring":
                if($length < 255) 
                    return "C2($length)";
                
                return "X2";
            case "clob":
                return "XL";
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
