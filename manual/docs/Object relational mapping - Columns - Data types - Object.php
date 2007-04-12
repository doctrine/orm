Doctrine supports objects as column types. Basically you can set an object to a field and Doctrine handles automatically the serialization / unserialization
of that object.

<code type="php">
class Test extends Doctrine_Record {
    public function setTableDefinition() {
        $this->hasColumn('objecttest', 'object');
    }
}
</code>
