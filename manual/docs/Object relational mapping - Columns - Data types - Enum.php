Doctrine has a unified enum type. Enum typed columns automatically convert the string values into index numbers and vice versa. The possible values for the column
can be specified with Doctrine_Record::setEnumValues(columnName, array values).

<code type="php">
class Test extends Doctrine_Record {
    public function setTableDefinition() {
        $this->hasColumn('enumtest', 'enum', 4, 
                         array(
                            'values' => array(
                                        'php',
                                        'java',
                                        'python'
                                        )
                               )
			 );
    }
}
</code>
