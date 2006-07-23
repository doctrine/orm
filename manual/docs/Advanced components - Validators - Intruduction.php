With Doctrine validators you can validate a whole transaction and get info of everything
that went wrong. Some Doctrine validators also act as a database level constraints. For example
adding a unique validator to column 'name' also adds a database level unique constraint into that 
column.
<br \><br \>
Validators are added as a 4 argument for hasColumn() method. Validators should be separated
by '|' mark. For example email|unique would validate a value using Doctrine_Validator_Email
and Doctrine_Validator_Unique.
<br \><br \>
Doctrine has many predefined validators (see chapter 13.3). If you wish to use
custom validators just write *Validator classes and doctrine will automatically use them.
