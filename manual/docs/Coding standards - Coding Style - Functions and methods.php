<?php ?>
<ul>
<li \>Methods must be named by following the naming conventions.
</ul>
<ul>
<li \>Methods must always declare their visibility by using one of the private, protected, or public constructs.
</ul>
<ul>
<li \>Like classes, the brace is always written right after the method name. There is no space between the function name and the opening parenthesis for the arguments.
</ul>
<ul>
<li \>Functions in the global scope are strongly discouraged.
</ul>
<ul>
<li \>This is an example of an acceptable function declaration in a class:
</ul>

<?php
renderCode("<?php
/**
 * Documentation Block Here
 */
class Foo {
    /**
     * Documentation Block Here
     */
    public function bar() {
        // entire content of function
        // must be indented four spaces
    }
}");

?>
<ul>
<li \>Passing by-reference is permitted in the function declaration only:
</ul>
<?php
renderCode("<?php
/** 
 * Documentation Block Here 
 */
class Foo {    
    /**
     * Documentation Block Here
     */
    public function bar(&\$baz) {
    }
}
");
?>
<ul>
<li \>Call-time pass by-reference is prohibited.
</ul>
<ul>
<li \>The return value must not be enclosed in parentheses. This can hinder readability and can also break code if a method is later changed to return by reference.
</ul>
<?php
renderCode("<?php
/** 
 * Documentation Block Here 
 */
class Foo {    
    /**     
     * WRONG     
     */    
    public function bar() {        
        return(\$this->bar);
    }    
    /**     
     * RIGHT     
     */    
    public function bar() {        
        return \$this->bar;
    }
}");
?>
<ul>
<li \>Function arguments are separated by a single trailing space after the comma delimiter. This is an example of an acceptable function call for a function that takes three arguments:
</ul>
<?php
renderCode("<?php
threeArguments(1, 2, 3);
?>");
?>
<ul>
<li \>Call-time pass by-reference is prohibited. See the function declarations section for the proper way to pass function arguments by-reference.
</ul>
<ul>
<li \>For functions whose arguments permitted arrays, the function call may include the "array" construct and can be split into multiple lines to improve readability. In these cases, the standards for writing arrays still apply:
</ul>
<?php
renderCode("<?php
threeArguments(array(1, 2, 3), 2, 3);

threeArguments(array(1, 2, 3, 'Framework',
                     'Doctrine', 56.44, 500), 2, 3);
?>");
?>

