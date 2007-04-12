Now that you know how to specify your business rules in your models, it is time to look at how to 
deal with these rules in the rest of your application.<br />
<br />
Implicit validation:<br />
Whenever a record is going to be saved to the persistent data store (i.e. through calling $record->save())
the full validation procedure is executed. If errors occur during that process an exception of the type
Doctrine_Validator_Exception will be thrown. You can catch that exception and analyze the errors by
using the instance method Doctine_Validator_Exception::getInvalidRecords(). This method returns
an ordinary array with references to all records that did not pass validation. You can then
further explore the errors of each record by analyzing the error stack of each record.
The error stack of a record can be obtained with the instance method Doctrine_Record::getErrorStack().
Each error stack is an instance of the class Doctrine_Validator_ErrorStack. The error stack
provides an easy to use interface to inspect the errors.<br />
<br />
Explicit validation:<br />
You can explicitly trigger the validation for any record at any time. For this purpose Doctrine_Record
provides the instance method Doctrine_Record::isValid(). This method returns a boolean value indicating
the result of the validation. If the method returns FALSE, you can inspect the error stack in the same
way as seen above except that no exception is thrown, so you simply obtain
the error stack of the record that didnt pass validation through Doctrine_Record::getErrorStack().<br />
<br />
The following code snippet shows an example of handling implicit validation which caused a Doctrine_Validator_Exception.

<code type="php">
try {
    $user->name = "this is an example of too long name";
    $user->Email->address = "drink@@notvalid..";
    $user->save();
} catch(Doctrine_Validator_Exception $e) {
    // Note: you could also use $e->getInvalidRecords(). The direct way
    // used here is just more simple when you know the records you're dealing with.
    $userErrors = $user->getErrorStack();
    $emailErrors = $user->Email->getErrorStack();
    
    /* Inspect user errors */
    foreach($userErrors as $fieldName => $errorCodes) {
        switch ($fieldName) {
            case 'name':
                // $user->name is invalid. inspect the error codes if needed.
            break;
        }
    }
    
    /* Inspect email errors */
    foreach($emailErrors as $fieldName => $errorCodes) {
        switch ($fieldName) {
            case 'address':
                // $user->Email->address is invalid. inspect the error codes if needed.
            break;
        }
    }
}
</code>
