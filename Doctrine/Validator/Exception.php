<?php
Doctrine::autoload('Doctrine_Exception');

class Doctrine_Validator_Exception extends Doctrine_Exception {
    /**
     * @var Doctrine_Validator $validator
     */
    private $validator;
    /**
     * @param Doctrine_Validator $validator
     */
    public function __construct(Doctrine_Validator $validator) {
        $this->validator = $validator;
    }
    /**
     * returns the error stack
     *
     * @return array
     */
    public function getErrorStack() {
        return $this->validator->getErrorStack();
    }
    /**
     * __toString
     *
     * @return string
     */
    public function __toString() {
        $string = "Error stack : ".print_r($this->validator->getErrorStack(), true);
        return $string.parent::__toString();
    }
}
?>
