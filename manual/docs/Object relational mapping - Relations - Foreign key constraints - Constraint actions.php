//CASCADE//:
Delete or update the row from the parent table and automatically delete or update the matching rows in the child table. Both ON DELETE CASCADE and ON UPDATE CASCADE are supported. Between two tables, you should not define several ON UPDATE CASCADE clauses that act on the same column in the parent table or in the child table.

//SET NULL// :
Delete or update the row from the parent table and set the foreign key column or columns in the child table to NULL. This is valid only if the foreign key columns do not have the NOT NULL qualifier specified. Both ON DELETE SET NULL and ON UPDATE SET NULL clauses are supported.

//NO ACTION// :
In standard SQL, NO ACTION means no action in the sense that an attempt to delete or update a primary key value is not allowed to proceed if there is a related foreign key value in the referenced table.

//RESTRICT// :
Rejects the delete or update operation for the parent table. NO ACTION and RESTRICT are the same as omitting the ON DELETE or ON UPDATE clause.

//SET DEFAULT// :

In the following example we define two classes, User and Phonenumber with their relation being one-to-many. We also add a foreign key constraint with onDelete cascade action.

<code type='php'>
class User extends Doctrine_Record
{
    public function setUp()
    {
        $this->hasMany('Phonenumber', 'Phonenumber.user_id', array('onDelete' => 'cascade'));
    }
    public function setTableDefinition()
    {
        $this->hasColumn('name', 'string', 50);
        $this->hasColumn('loginname', 'string', 20);
        $this->hasColumn('password', 'string', 16);
    }
}
class Phonenumber extends Doctrine_Record
{
    public function setTableDefinition()
    {
        $this->hasColumn('phonenumber', 'string', 50);
        $this->hasColumn('user_id', 'integer');
    }
}
</code>
