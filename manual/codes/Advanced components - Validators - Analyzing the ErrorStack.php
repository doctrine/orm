<?php
try {
    $user->name = "this is an example of too long name";
    $user->Email->address = "drink@@notvalid..";
    $user->save();
} catch(Doctrine_Validator_Exception $e) {
    $stack = $e->getErrorStack();
    foreach($stack as $component => $err) {
        foreach($err as $field => $type) {
            switch($type):
                case Doctrine_Validator::ERR_TYPE:
                    print $field." is not right type";
                break;
                case Doctrine_Validator::ERR_UNIQUE:
                    print $field." is not unique";
                break;
                case Doctrine_Validator::ERR_VALID:
                    print $field." is not valid";
                break;
                case Doctrine_Validator::ERR_LENGTH:
                    print $field." is too long";
                break;
            endswitch;
        }
    }
}
?>
