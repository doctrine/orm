<?php ?>
You can add indexes by simple calling Doctrine_Record::index('indexName', $definition) where $definition is the
definition array.
<br \><br \>
An example of adding a simple index to field called 'name':
<br \><br \>
<?php
renderCode("<?php
class IndexTest extends Doctrine_Record
{
    public function setTableDefinition()
    {
        \$this->hasColumn('name', 'string');

        \$this->index('myindex', array('fields' => 'name');
    }
}
?>");
?>
<br \><br \>
An example of adding a multi-column index to field called 'name':
<br \><br \>
<?php
renderCode("<?php
class MultiColumnIndexTest extends Doctrine_Record
{
    public function setTableDefinition() 
    {
        \$this->hasColumn('name', 'string');
        \$this->hasColumn('code', 'string');

        \$this->index('myindex', array('fields' => array('name', 'code')));
    }
}
?>");
?>
<br \><br \>
An example of adding a multiple indexes on same table:
<br \><br \>
<?php
renderCode("<?php
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
?>");
?>
