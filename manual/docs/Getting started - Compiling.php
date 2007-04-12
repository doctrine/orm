<p>
  Compiling is a method for making a single file of most used doctrine runtime components
  including the compiled file instead of multiple files (in worst cases dozens of files)
  can improve performance by an order of magnitude.
</p>
<p>
  In cases where this might fail, a Doctrine_Exception is throw detailing the error.
</p>

<code type="php">
Doctrine::compile();

// on some other script:

require_once("path_to_doctrine/Doctrine.compiled.php");
</code>
