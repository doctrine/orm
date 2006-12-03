<?php
class Test extends Doctrine_Record {
    public function setTableDefinition() {
        $this->hasColumn('enumtest', 'enum', 4, 
                         array(
                            'values' => array(
                                        'php',
                                        'java',
                                        'python'
                                        )
                               );
    }
}
?>
