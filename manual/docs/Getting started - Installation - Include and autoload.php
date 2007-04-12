In order to use Doctrine in your project it must first be included.

<code type='php'>
require_once('path-to-doctrine/lib/Doctrine.php');
</code>

Doctrine support [http://www.php.net/autoload Autoloading] for including files so that you do not have to include anything more then the base file. There are two different strategies that can be used to do this:

If you do use the **__autoload** function for your own logic you can use it. 

<code type='php'>
function __autoload($class) {
	Doctrine::autoload($class);
}
</code>

If your project uses autoload and/or you have other libraries that use it you could use [http://www.php.net/manual/en/function.spl-autoload-register.php spl_autoload_register] to register more then one autoloading function. 

<code type="php">
spl_autoload_register(array('Doctrine', 'autoload'));
</code>
