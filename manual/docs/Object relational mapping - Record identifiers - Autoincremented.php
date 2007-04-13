Autoincrement primary key is the most basic identifier and its usage is strongly encouraged. Sometimes you may want to use some other name than 'id'
for your autoinc primary key. It can be specified as follows:

<code type="php">
class User extends Doctrine_Record {
    public function setTableDefinition() {
        $this->hasColumn('uid','integer',20,'primary|autoincrement');

    }
}
</code>
