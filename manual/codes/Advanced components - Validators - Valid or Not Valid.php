<?php
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
?>
