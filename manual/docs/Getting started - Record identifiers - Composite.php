Composite primary key can be used efficiently in association tables (tables that connect two components together). It is not recommended
to use composite primary keys in anywhere else as Doctrine does not support mapping relations on multiple columns.
<br \><br \>
Due to this fact your doctrine-based system will scale better if it has autoincremented primary key even for association tables.
