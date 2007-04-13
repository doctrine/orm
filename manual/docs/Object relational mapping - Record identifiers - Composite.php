Composite primary key can be used efficiently in association tables (tables that connect two components together). It is not recommended
to use composite primary keys in anywhere else as Doctrine does not support mapping relations on multiple columns.



Due to this fact your doctrine-based system will scale better if it has autoincremented primary key even for association tables.

<code type="php">
class Groupuser extends Doctrine_Record {
    public function setTableDefinition() {
        $this->hasColumn('user_id', 'integer', 20, 'primary');
        $this->hasColumn('group_id', 'integer', 20, 'primary');
    }
}
</code>
