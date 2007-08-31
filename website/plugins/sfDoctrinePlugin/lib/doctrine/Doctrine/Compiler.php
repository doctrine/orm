<?php
/*
 *  $Id: Compiler.php 1768 2007-06-19 22:55:34Z zYne $
 *
 * THIS SOFTWARE IS PROVIDED BY THE COPYRIGHT HOLDERS AND CONTRIBUTORS
 * "AS IS" AND ANY EXPRESS OR IMPLIED WARRANTIES, INCLUDING, BUT NOT
 * LIMITED TO, THE IMPLIED WARRANTIES OF MERCHANTABILITY AND FITNESS FOR
 * A PARTICULAR PURPOSE ARE DISCLAIMED. IN NO EVENT SHALL THE COPYRIGHT
 * OWNER OR CONTRIBUTORS BE LIABLE FOR ANY DIRECT, INDIRECT, INCIDENTAL,
 * SPECIAL, EXEMPLARY, OR CONSEQUENTIAL DAMAGES (INCLUDING, BUT NOT
 * LIMITED TO, PROCUREMENT OF SUBSTITUTE GOODS OR SERVICES; LOSS OF USE,
 * DATA, OR PROFITS; OR BUSINESS INTERRUPTION) HOWEVER CAUSED AND ON ANY
 * THEORY OF LIABILITY, WHETHER IN CONTRACT, STRICT LIABILITY, OR TORT
 * (INCLUDING NEGLIGENCE OR OTHERWISE) ARISING IN ANY WAY OUT OF THE USE
 * OF THIS SOFTWARE, EVEN IF ADVISED OF THE POSSIBILITY OF SUCH DAMAGE.
 *
 * This software consists of voluntary contributions made by many individuals
 * and is licensed under the LGPL. For more information, see
 * <http://www.phpdoctrine.com>.
 */
/**
 * Doctrine_Compiler
 * This class can be used for compiling the entire Doctrine framework into a single file
 *
 * @package     Doctrine
 * @author      Konsta Vesterinen <kvesteri@cc.hut.fi>
 * @license     http://www.opensource.org/licenses/lgpl-license.php LGPL
 * @category    Object Relational Mapping
 * @link        www.phpdoctrine.com
 * @since       1.0
 * @version     $Revision: 1768 $
 */
class Doctrine_Compiler
{
    /**
     * method for making a single file of most used doctrine runtime components
     * including the compiled file instead of multiple files (in worst
     * cases dozens of files) can improve performance by an order of magnitude
     *
     * @throws Doctrine_Compiler_Exception      if something went wrong during the compile operation
     * @return void
     */
    public static function compile($target = null)
    {
        $path = Doctrine::getPath();
        $it = new RecursiveIteratorIterator(new RecursiveDirectoryIterator($path), RecursiveIteratorIterator::LEAVES_ONLY);

        foreach ($it as $file) {
            $e = explode('.', $file->getFileName());
            
            // we don't want to require versioning files
            if (end($e) === 'php' && strpos($file->getFileName(), '.inc') === false) {
                require_once $file->getPathName();
            }
        }

        $classes = array_merge(get_declared_classes(), get_declared_interfaces());

        $ret     = array();

        foreach ($classes as $class) {
            $e = explode('_', $class);

            if ($e[0] !== 'Doctrine') {
                continue;
            }
            $refl  = new ReflectionClass($class);
            $file  = $refl->getFileName();
            
            print 'Adding ' . $file . PHP_EOL;

            $lines = file($file);

            $start = $refl->getStartLine() - 1;
            $end   = $refl->getEndLine();

            $ret = array_merge($ret, array_slice($lines, $start, ($end - $start)));
        }

        if ($target == null) {
            $target = $path . DIRECTORY_SEPARATOR . 'Doctrine.compiled.php';
        }

        // first write the 'compiled' data to a text file, so
        // that we can use php_strip_whitespace (which only works on files)
        $fp = @fopen($target, 'w');

        if ($fp === false) {
            throw new Doctrine_Compiler_Exception("Couldn't write compiled data. Failed to open $target");
        }
        fwrite($fp, "<?php ". implode('', $ret));
        fclose($fp);

        $stripped = php_strip_whitespace($target);
        $fp = @fopen($target, 'w');
        if ($fp === false) {
            throw new Doctrine_Compiler_Exception("Couldn't write compiled data. Failed to open $file");
        }
        fwrite($fp, $stripped);
        fclose($fp);
    }
}
