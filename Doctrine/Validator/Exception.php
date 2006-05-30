<?php
require_once(Doctrine::getPath().DIRECTORY_SEPARATOR."Exception.php");

class Doctrine_Validator_Exception extends Doctrine_Exception {
    private $validator;
    public function __construct(Doctrine_Validator $validator) {
        $this->validator = $validator;
    }
    public function getErrorStack() {
        return $this->validator->getErrorStack();
    }
}
?>
