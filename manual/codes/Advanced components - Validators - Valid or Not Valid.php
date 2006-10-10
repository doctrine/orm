<?php
try {
    $user->name = "this is an example of too long name";
    $user->Email->address = "drink@@notvalid..";
    $user->save();
} catch(Doctrine_Validator_Exception $e) {
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
