<?php ?>
Doctrine supports sequences for generating record identifiers. Sequences are a way of offering unique IDs for data rows. If you do most of your work with e.g. MySQL, think of sequences as another way of doing AUTO_INCREMENT. 
<br \><br \>
Doctrine knows how to do sequence generation in the background so you don't have to worry about calling database specific queries - Doctrine does it for you, all you need to do
is define a column as a sequence column and optionally provide the name of the sequence table and the id column name of the sequence table.
<br \><br \>
Consider the following record definition:
<br \><br \>
<?php
renderCode("<?php
class Book extends Doctrine_Record {
    public function setTableDefinition()
    {
        \$this->hasColumn('id', 'integer', null, array('primary', 'sequence'));
        \$this->hasColumn('name', 'string');
    }
}
?>");
?>
<br \><br \>
By default Doctrine uses the following format for sequence tables [tablename]_seq. If you wish to change this you can use the following 
piece of code to change the formatting:
<br \><br \>
<?php
renderCode("<?php
\$manager = Doctrine_Manager::getInstance();
\$manager->setAttribute(Doctrine::ATTR_SEQNAME_FORMAT, 
'%s_my_seq');
?>");
?>
<br \><br \>
Doctrine uses column named id as the sequence generator column of the sequence table. If you wish to change this globally (for all connections and all tables)
you can use the following code:
<br \><br \>
<?php
renderCode("<?php
\$manager = Doctrine_Manager::getInstance();
\$manager->setAttribute(Doctrine::ATTR_SEQCOL_NAME,
'my_seq_column');
?>");
?>
<br \><br \>
In the following example we do not wish to change global configuration we just want to make the id column to use sequence table called
book_sequence. It can be done as follows:  <br \><br \>
<?php
renderCode("<?php
class Book extends Doctrine_Record {
    public function setTableDefinition()
    {
        \$this->hasColumn('id', 'integer', null, array('primary', 'sequence' => 'book_sequence'));
        \$this->hasColumn('name', 'string');
    }
}
?>");
?>
<br \><br \>
Here we take the preceding example a little further: we want to have a custom sequence column. Here it goes:<br \><br \>
<?php
renderCode("<?php
class Book extends Doctrine_Record {
    public function setTableDefinition()
    {
        \$this->hasColumn('id', 'integer', null, array('primary', 'sequence' => array('book_sequence', 'sequence')));
        \$this->hasColumn('name', 'string');
    }
}
?>");
?>

