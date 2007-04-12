<ul>
<li \>Negative numbers are not permitted as indices.
</ul>
<ul>
<li \>An indexed array may be started with any non-negative number, however this is discouraged and it is recommended that all arrays have a base index of 0.
</ul>
<ul>
<li \>When declaring indexed arrays with the array construct, a trailing space must be added after each comma delimiter to improve readability.
</ul>
<ul>
<li \>It is also permitted to declare multiline indexed arrays using the "array" construct. In this case, each successive line must be padded with spaces.
</ul>
<ul>
<li \>When declaring associative arrays with the array construct, it is encouraged to break the statement into multiple lines. In this case, each successive line must be padded with whitespace such that both the keys and the values are aligned:
</ul>

<code type="php">
$sampleArray = array('Doctrine', 'ORM', 1, 2, 3);


$sampleArray = array(1, 2, 3, 
                     $a, $b, $c,                     
                     56.44, $d, 500);


$sampleArray = array('first'  => 'firstValue',
                     'second' => 'secondValue');

