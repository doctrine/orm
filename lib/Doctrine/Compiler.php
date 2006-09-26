<?php
/*
 *  $Id$
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
 * Doctrine
 * the base class of Doctrine framework
 *
 * @package     Doctrine
 * @author      Konsta Vesterinen
 * @license     LGPL
 */
class Doctrine_Compiler {
    /**
     * @var array $classes          an array containing all runtime classes of Doctrine framework
     */
    private static $classes = array(
                         "Doctrine",
                         "Configurable",
                         "Manager",
                         "Session",
                         "Table",
                         "Iterator",
                         "Exception",
                         "Access",
                         "Null",
                         "Identifier",
                         "Repository",
                         "Record",
                         "Record_Iterator",
                         "Collection",
                         "Collection_Immediate",
                         "Validator",
                         "Hydrate",
                         "Query",
                         "Query_Part",
                         "Query_From",
                         "Query_Orderby",
                         "Query_Groupby",
                         "Query_Condition",
                         "Query_Where",
                         "Query_Having",
                         "RawSql",
                         "EventListener_Interface",
                         "EventListener",
                         "EventListener_Empty",
                         "Relation",
                         "ForeignKey",
                         "LocalKey",
                         "Association",
                         "DB",
                         "DBStatement",
                         "Connection",
                         "Connection_UnitOfWork",
                         "Connection_Transaction");

    /**
     * getRuntimeClasses
     * returns an array containing all runtime classes of Doctrine framework
     *
     * @return array
     */
    public static function getRuntimeClasses() {
        return self::$classes;
    }
    /**
     * method for making a single file of most used doctrine runtime components
     * including the compiled file instead of multiple files (in worst
     * cases dozens of files) can improve performance by an order of magnitude
     *
     * @throws Doctrine_Exception
     * @return void
     */
    public static function compile($target = null) {
        $path = Doctrine::getPath();

        $classes = self::$classes;

        $ret     = array();

        foreach($classes as $class) {
            if($class !== 'Doctrine')
                $class = 'Doctrine_'.$class;

            $file  = $path.DIRECTORY_SEPARATOR.str_replace("_",DIRECTORY_SEPARATOR,$class).".php";
            
            echo "Adding $file" . PHP_EOL;

            if( ! file_exists($file))
                throw new Doctrine_Exception("Couldn't compile $file. File $file does not exists.");

            Doctrine::autoload($class);
            $refl  = new ReflectionClass ( $class );
            $lines = file( $file );

            $start = $refl -> getStartLine() - 1;
            $end   = $refl -> getEndLine();

            $ret = array_merge($ret,
                               array_slice($lines,
                               $start,
                              ($end - $start)));

        }

        if ($target == null) {
            $target = $path.DIRECTORY_SEPARATOR.'Doctrine.compiled.php';
        }            

        // first write the 'compiled' data to a text file, so
        // that we can use php_strip_whitespace (which only works on files)
        $fp = @fopen($target, 'w');
        
        if ($fp === false)
            throw new Doctrine_Exception("Couldn't write compiled data. Failed to open $target");
            
        fwrite($fp, "<?php".
                    " class InvalidKeyException extends Exception { }".
                    " class DQLException extends Exception { }".
                    implode('', $ret)
              );
        fclose($fp);

        $stripped = php_strip_whitespace($target);
        $fp = @fopen($target, 'w');
        if ($fp === false)
            throw new Doctrine_Exception("Couldn't write compiled data. Failed to open $file");
        fwrite($fp, $stripped);
        fclose($fp);
    }
}
