
You can add indexes by simple calling Doctrine_Record::index('indexName', $definition) where $definition is the
definition array.



An example of adding a simple index to field called 'name':



<code type="php">
class IndexTest extends Doctrine_Record
{
    public function setTableDefinition()
    {
        \$this->hasColumn('name', 'string');

        \$this->index('myindex', array('fields' => 'name');
    }
}
?></code>



An example of adding a multi-column index to field called 'name':



<code type="php">
class MultiColumnIndexTest extends Doctrine_Record
{
    public function setTableDefinition() 
    {
        \$this->hasColumn('name', 'string');
        \$this->hasColumn('code', 'string');

        \$this->index('myindex', array('fields' => array('name', 'code')));
    }
}
?></code>



An example of adding a multiple indexes on same table:



<code type="php">
class MultipleIndexTest extends Doctrine_Record
{
    public function setTableDefinition() 
    {
        \$this->hasColumn('name', 'string');
        \$this->hasColumn('code', 'string');
        \$this->hasColumn('age', 'integer');

        \$this->index('myindex', array('fields' => array('name', 'code')));
        \$this->index('ageindex', array('fields' => array('age'));
    }
}
?></code>
