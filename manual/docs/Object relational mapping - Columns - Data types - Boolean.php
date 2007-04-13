The boolean data type represents only two values that can be either 1 or 0.
Do not assume that these data types are stored as integers because some DBMS drivers may implement this
type with single character text fields for a matter of efficiency.
Ternary logic is possible by using null as the third possible value that may be assigned to fields of this type.  


<code type="php">
class Test extends Doctrine_Record {
    public function setTableDefinition() {
        $this->hasColumn('booltest', 'boolean');
    }
}
</code>
