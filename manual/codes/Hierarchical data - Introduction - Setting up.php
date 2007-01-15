<?php
class Menu extends Doctrine_Record { 
    public function setTableDefinition() {

        $this->setTableName('menu');

        // add this your table definition to set the table as NestedSet tree implementation
		// $implName is 'NestedSet' or 'AdjacencyList' or 'MaterializedPath'
		// $options is an assoc array of options, see implementation docs for options
        $this->actsAsTree($implName, $options);
       
        // you do not need to add any columns specific to the nested set implementation, these are added for you
        $this->hasColumn("name","string",30);

    }
    
    // this __toString() function is used to get the name for the path, see node::getPath()
    public function __toString() {
        return $this->get('name');
    }
}
?>