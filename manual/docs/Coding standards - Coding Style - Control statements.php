<?php ?>
<ul>
<li \>Control statements based on the if and elseif constructs must have a single space before the opening parenthesis of the conditional, and a single space after the closing parenthesis.
</ul>
<ul>
<li \>Within the conditional statements between the parentheses, operators must be separated by spaces for readability. Inner parentheses are encouraged to improve logical grouping of larger conditionals.
</ul>
<ul>
<li \>The opening brace is written on the same line as the conditional statement. The closing brace is always written on its own line. Any content within the braces must be indented four spaces.
</ul>
<?php
renderCode("<?php
if (\$foo != 2) {
    \$foo = 2;
}");
?>
<ul>
<li \>For "if" statements that include "elseif" or "else", the formatting must be as in these examples:
</ul>
<?php
renderCode("<?php
if (\$foo != 1) {
    \$foo = 1;
} else {   
    \$foo = 3;
}
if (\$foo != 2) {
    \$foo = 2;
} elseif (\$foo == 1) {
    \$foo = 3;
} else {   
    \$foo = 11;
}");
?>

<ul>
<li \>PHP allows for these statements to be written without braces in some circumstances, the following format for if statements is also allowed:
</ul>
<?php
renderCode("<?php
if (\$foo != 1)
    \$foo = 1;
else
    \$foo = 3;

if (\$foo != 2)
    \$foo = 2;
elseif (\$foo == 1)
    \$foo = 3;
else
    \$foo = 11;
");
?>
<ul>
<li \>Control statements written with the "switch" construct must have a single space before the opening parenthesis of the conditional statement, and also a single space after the closing parenthesis.
</ul>
<ul>
<li \>All content within the "switch" statement must be indented four spaces. Content under each "case" statement must be indented an additional four spaces but the breaks must be at the same indentation level as the "case" statements.
</ul>
<?php
renderCode("<?php
switch (\$case) {
    case 1:
    case 2:
    break;
    case 3:
    break;
    default:
    break;
}
?>");
?>
<ul>
<li \>The construct default may never be omitted from a switch statement.
</ul>
