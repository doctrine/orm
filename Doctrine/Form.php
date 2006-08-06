<?php
class Doctrine_Form implements Iterator {
    protected $record;
    protected $elements = array();
    protected $columns;
    protected $current;
    protected $keys;
    protected $index;
    protected $count;
    public function __construct(Doctrine_Record $record) {
        $this->record = $record;
        $this->columns = $record->getTable()->getColumns();
        $this->keys    = array_keys($this->columns);
        $this->index   = 0;
        $this->count   = count($this->keys);
    }
    public function current() {
        $i = $this->index;
        $column = $this->keys[$i];

        $definitions = $this->columns[$column];

        $e    = explode("|",$definitions[2]);
        
        
        $enum = false;
        
        if($definitions[0] == "enum")
            $enum = $this->record->getTable()->getEnumValues($column);

        $length = $definitions[1];
        if( ! in_array("autoincrement",$e) && ! in_array("protected",$e)) {
            if($enum) {
                $elements[$column] = "<select name='data[$column]'>\n";
				$elements[$column] .= "	  <option value='-'>-</option>\n";
                foreach($enum as $k => $v) {
                    if($this->record->get($column) == $v) {
                        $str = 'selected';
                    } else
                        $str = '';

                    $elements[$column] .= "    <option value='$v' $str>$v</option>\n";
                }
                $elements[$column] .= "</select>\n";
            } else {
                if($length <= 255) {
                    $elements[$column] = "<input name='data[$column]' type='text' value='".$this->record->get($column)."' maxlength=$length \>\n";
                } elseif($length <= 4000) {
                    $elements[$column] = "<textarea name='data[$column]' cols=40 rows=10>".$this->record->get($column)."</textarea>\n";
                } else {
                    $elements[$column] = "<textarea name='data[$column]' cols=80 rows=25>".$this->record->get($column)."</textarea>\n";
                }
            }
            return $elements[$column];
        } else {
            $this->index++;

            if($this->index < $this->count)
                return self::current();
        }
    }
    public function key() {
        $i = $this->index;
        return $this->keys[$i];
    }
    public function next() {
        $this->index++;
    }
    public function rewind() {
        $this->index = 0;
    }
    public function valid() {
        if($this->index >= $this->count)
            return false;
            
        return true;
    }
}
?>
