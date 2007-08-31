<?php
function mixin($tpl, $method = null)
{
    static $_map;
    
    if ( ! isset($_map[$tpl . $method])) {
        if ($method === null) {
            $refl = new ReflectionFunction($tpl);
        } else {
            $refl = new ReflectionMethod($tpl, $method);
        }
    
        $lines = file($refl->getFileName());
    
        $start = $refl->getStartLine();
        $end   = $refl->getEndLine();
    
        $ret = array_slice($lines, $start, ($end - $start));
    
        $code = trim(trim(implode(' ', $ret)), '{}');

        $_map[$tpl . $method] = $code;
    } else {
        $code = $_map[$tpl . $method];      	
    }
    eval($code);
}
function someCode() {
    $a = 10;
}
class Template 
{
    public function exec()
    {
        $a = 10;
    }
}
print "<pre>MIXIN BENCHMARK \n";

$timepoint = microtime(true);

$i = 500;

while ($i--) {
    mixin('someCode');
}

print 'EXECUTED 500 CODE BLOCKS : ' . (microtime(true) - $timepoint) . "\n";


$timepoint = microtime(true);

$i = 500;

while ($i--) {
    someCode();
}

print 'EXECUTED 500 DIRECT FUNCTION CALLS : ' . (microtime(true) - $timepoint) . "\n";

$timepoint = microtime(true);

$i = 500;

while ($i--) {
    eval('$a = 10;');
}

print 'EXECUTED 500 DIRECT EVAL CALLS : ' . (microtime(true) - $timepoint) . "\n";
