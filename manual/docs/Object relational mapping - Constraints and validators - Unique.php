Unique constraints ensure that the data contained in a column or a group of columns is unique with respect to all the rows in the table.

The following definition uses a unique constraint for column 'name'.

<code type='php'>
class User extends Doctrine_Record
{
    public function setTableDefinition()
    {
        $this->hasColumn('name', 'string', 200, array('unique' => true));
    }
}
</code>

>> Note: You should only use unique constraints for other than primary key columns. Primary key columns are always unique.

The following definition adds a unique constraint for columns 'name' and 'age'.

<code type='php'>
class User extends Doctrine_Record
{
    public function setTableDefinition()
    {
        $this->hasColumn('name', 'string', 200);
        $this->hasColumn('age', 'integer', 2);
        
        $this->unique(array('name', 'age'));
    }
}
</code> 
