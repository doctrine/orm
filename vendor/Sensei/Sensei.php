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
 * <http://www.phpdoctrine.org>.
 */

/**
 * Sensei
 *
 * @package     Sensei
 * @category    Core
 * @license     http://www.gnu.org/licenses/lgpl.txt LGPL
 * @link        http://sourceforge.net/projects/sensei
 * @author      Janne Vanhala <jpvanhal@cc.hut.fi>
 * @author      Konsta Vesterinen <kvesteri@cc.hut.fi>
 * @version     $Revision$
 * @since       1.0
 */
final class Sensei {
    /**
     * @var string $path            doctrine root directory
     */
    private static $path;

    /**
     * getPath
     * returns the doctrine root
     *
     * @return string
     */
    public static function getPath()
    {
        if ( !  self::$path) {
            self::$path = dirname(__FILE__);
        }
        return self::$path;
    }

    /**
     * simple autoload function
     * returns true if the class was loaded, otherwise false
     *
     * @param string $classname
     * @return boolean
     */
    public static function autoload($classname)
    {
        if (class_exists($classname, false)) {
            return false;
        }
        if ( ! self::$path) {
            self::$path = dirname(__FILE__);
        }
        $class = self::$path . DIRECTORY_SEPARATOR . str_replace('_', DIRECTORY_SEPARATOR, $classname) . '.php';

        if ( ! file_exists($class)) {
            return false;
        }

        require_once($class);

        return true;
    }

    /**
     * Load a given class: a file name is acquired by naively transforming
     * underscores into directory separators and appending the .php suffix.
     * 
     * The file is searched for in every directory in the include path.
     * 
     * @param string class name
     * @param boolean allow class to be autoloaded before attempt
     * @return true
     * @throws Xi_Exception if the class could not be loaded
     */
    public static function loadClass($className)
    {
        if (class_exists($className, false)) {
            return false;
        }
        if ( !  self::$path) {
            self::$path = dirname(__FILE__);
        }
        $class = self::$path . DIRECTORY_SEPARATOR . str_replace('_', DIRECTORY_SEPARATOR, $className) . '.php';

        if ( ! file_exists($class)) {
            return false;
        }

        require_once($class);

        throw new Sensei_Exception('Class ' . $className . ' does not exist and could not '
                                 . 'be loaded.');
    }

    /**
     * Create a new instance of a class.
     * 
     * @param string class name
     * @param array constructor arguments, optional
     * @return object
     */
    public static function create($class, array $args = array())
    {
        /**
         * An arbitrary amount of constructor arguments can be achieved using
         * reflection, but it's slower by an order of magnitude. Manually handle
         * instantiation for up to three arguments.
         */
        switch (count($args)) {
            case 0:
                return new $class;
            case 1:
                return new $class($args[0]);
            case 2:
                return new $class($args[0], $args[1]);
            case 3:
                return new $class($args[0], $args[1], $args[2]);
            default:
                return call_user_func_array(array(new ReflectionClass($class),'newInstance'),
                                            $args);
        }
    }
}
