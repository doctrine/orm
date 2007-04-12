The integer type is the same as integer type in PHP. It may store integer values as large as each DBMS may handle. 
 <br \><br \>
Fields of this type may be created optionally as unsigned integers but not all DBMS support it. 
Therefore, such option may be ignored. Truly portable applications should not rely on the availability of this option.
 <br \><br \>
The integer type maps to different database type depending on the column length.

<code type="php">
class Test extends Doctrine_Record {
    public function setTableDefinition() {
        $this->hasColumn('integertest', 'integer', 4, array('unsigned' => true));
    }
}
</code>
