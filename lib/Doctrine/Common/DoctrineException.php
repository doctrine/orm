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
 * <http://www.doctrine-project.org>.
 */
 
namespace Doctrine\Common;

/** 
 * Base Exception class of Doctrine
 *
 * @license http://www.opensource.org/licenses/lgpl-license.php LGPL
 * @link    www.doctrine-project.org
 * @since   2.0
 * @version $Revision: 3938 $
 * @author  Guilherme Blanco <guilhermeblanco@hotmail.com>
 * @author  Jonathan Wage <jonwage@gmail.com>
 * @author  Roman Borschel <roman@code-factory.org>
 */
class DoctrineException extends \Exception
{
    /**
     * @var array Lazy initialized array of error messages
     * @static
     */
    private static $_messages = array();

    /**
     * Initializes a new DoctrineException.
     *
     * @param string $message
     * @param Exception $cause Optional Exception
     */
    public function __construct($message = "", \Exception $cause = null)
    {
        $code = ($cause instanceof Exception) ? $cause->getCode() : 0;
        
        parent::__construct($message, $code, $cause);
    }
    
    /**
     * Throws a DoctrineException reporting not implemented method in a given class
     *
     * @static
     * @param string $method Method name
     * @param string $class  Class name
     * @throws DoctrineException
     */
    public static function notImplemented($method = null, $class = null)
    {
        if ($method && $class) {
            return new self("The method '$method' is not implemented in class '$class'.");
        } else if ($method && ! $class) {
            return new self($method);
        } else {
            return new self('Functionality is not implemented.');
        }
    }

    /**
     * Implementation of __callStatic magic method.
     *
     * Received a method name and arguments. It lookups a $_messages HashMap 
     * for matching Class#Method key and executes the returned string value
     * translating the placeholders with arguments passed.
     *
     * @static
     * @param string $method Method name
     * @param array $arguments Optional arguments to be translated in placeholders
     * @throws DoctrineException
     */
    public static function __callStatic($method, $arguments = array())
    {
        $class = get_called_class();
        $messageKey = substr($class, strrpos($class, '\\') + 1) . "#$method";

        $end = end($arguments);
        $innerException = null;
        
        if ($end instanceof \Exception) {
            $innerException = $end;
            unset($arguments[count($arguments) - 1]);
        }

        if (($message = self::getExceptionMessage($messageKey)) !== false) {
            $message = sprintf($message, $arguments);
        } else {
            $dumper  = function ($value) { return var_export($value, true); };
            $message = strtolower(preg_replace('~(?<=\\w)([A-Z])~', '_$1', $method));
            $message = ucfirst(str_replace('_', ' ', $message))
                     . ' (' . implode(', ', array_map($dumper, $arguments)) . ')';
        }
        
        return new $class($message, $innerException);
    }

    /**
     * Retrieves error string given a message key for lookup 
     *
     * @static
     * @param string $messageKey
     * @return string|false Returns the error string if found; FALSE otherwise
     */
    public static function getExceptionMessage($messageKey)
    {
        if ( ! self::$_messages) {
            // Lazy-init messages
            self::$_messages = array(
                'DoctrineException#partialObjectsAreDangerous' =>
                        "Loading partial objects is dangerous. Fetch full objects or consider " .
                        "using a different fetch mode. If you really want partial objects, " .
                        "set the doctrine.forcePartialLoad query hint to TRUE.",
                'QueryException#nonUniqueResult' =>
                        "The query contains more than one result."
            );
        }
        
        if (isset(self::$_messages[$messageKey])) {
            return self::$_messages[$messageKey];
        }
        
        return false;
    }
}