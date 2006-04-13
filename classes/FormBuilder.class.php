<?php
/**
 * Doctrine_Form_Builder
 */
class Doctrine_Form_Builder {
    public static function buildForm(Doctrine_Record $record) {

    }
}
class Doctrine_Element {
    private $attributes = array();
    private $data;
    
    public function toHtml() {
        return "<".$this->name.">"."</>";
    }
}
class InputElement {
    private $attributes = array();

}
?>
