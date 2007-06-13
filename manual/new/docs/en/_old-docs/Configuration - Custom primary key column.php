
<code type="php">
// custom primary key column name

class Group extends Doctrine_Record {
    public function setUp() {
        $this->setPrimaryKeyColumn("group_id");
    }
}
</code>
