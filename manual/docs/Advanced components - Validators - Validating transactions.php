When the validation attribute is set as true all transactions will be validated, so whenever Doctrine_Record::save(),
Doctrine_Session::flush() or any other saving method is used all the properties of all records in that transaction will have their values
validated.
<br \><br \>
Validation errors are being stacked into Doctrine_Validator_Exception.
