<?php
class Doctrine_Query_Printer
{
    protected $_indent = 0;
    protected $_silent;

    public function __construct($silent = false)
    {
        $this->_silent = $silent;
    }

    public function startProduction($name)
    {
        $this->println('(' . $name);
        $this->_indent++;
    }

    public function endProduction()
    {
        $this->_indent--;
        $this->println(')');
    }

    public function println($str)
    {
        if ( ! $this->_silent) {
            echo str_repeat('    ', $this->_indent), $str, "\n";
        }
    }
}
