<?php
class Doctrine_Validator_Unique {
    /**
     * @param Doctrine_Record $record
     * @param string $key
     * @param mixed $value
     * @param string $args
     * @return boolean
     */
    public function validate(Doctrine_Record $record, $key, $value, $args) {
        $table = $record->getTable();
        $sql   = "SELECT id FROM ".$table->getTableName()." WHERE ".$key." = ?";
        $stmt  = $table->getConnection()->getDBH()->prepare($sql);
        $stmt->execute(array($value));
        return ( ! is_array($stmt->fetch()));
    }
}
?>
