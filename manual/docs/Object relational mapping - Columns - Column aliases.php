Doctrine offers a way of setting column aliases. This can be very useful when you want to keep the application logic separate from the
database logic. For example if you want to change the name of the database field all you need to change at your application is the column definition.

<code type="php">
class Book extends Doctrine_Record 
{
    public function setTableDefinition() 
    {
        $this->hasColumn('bookName as name', 'string');
    }
}
$book = new Book();
$book->name = 'Some book';
$book->save();
</code>
