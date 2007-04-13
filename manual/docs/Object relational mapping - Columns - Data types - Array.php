This is the same as 'array' type in PHP. 

<code type="php">
class Test extends Doctrine_Record {
    public function setTableDefinition() {
        $this->hasColumn('arraytest', 'array', 10000);
    }
}
</code>
