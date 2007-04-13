The float data type may store floating point decimal numbers. This data type is suitable for representing numbers within a large scale range that do not require high accuracy. The scale and the precision limits of the values that may be stored in a database depends on the DBMS that it is used. 

<code type="php">
class Test extends Doctrine_Record {
    public function setTableDefinition() {
        $this->hasColumn('floattest', 'float');
    }
}
</code>
