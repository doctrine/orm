Natural identifier is a property or combination of properties that is unique and non-null. The use of natural identifiers
is discouraged. You should consider using autoincremented or sequential primary keys as they make your system more scalable.

<code type="php">
class User extends Doctrine_Record {
    public function setTableDefinition() {
        $this->hasColumn('name','string',200,'primary');
    }
}
</code>
