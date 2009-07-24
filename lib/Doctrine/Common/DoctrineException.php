<?php

namespace Doctrine\Common;

class DoctrineException extends \Exception
{
    private static $_messages = array();

    public function __construct($message = "", \Exception $cause = null)
    {
        parent::__construct($message, 0, $cause);
    }
    
    public static function notImplemented($method, $class)
    {
        return new self("The method '$method' is not implemented in class '$class'.");
    }

    public static function __callStatic($method, $arguments)
    {
        $class = get_called_class();
        $messageKey = substr($class, strrpos($class, '\\') + 1) . "#$method";

        $end = end($arguments);
        $innerException = null;
        if ($end instanceof \Exception) {
            $innerException = $end;
            unset($arguments[count($arguments) - 1]);
        }

        if ($message = self::getExceptionMessage($messageKey)) {
            $message = sprintf($message, $arguments);
        } else {
            $message  = strtolower(preg_replace('~(?<=\\w)([A-Z])~', '_$1', $method));
            $message  = ucfirst(str_replace('_', ' ', $message));
            $args = array();
            foreach ($arguments as $argument) {
                $args[] = var_export($argument, true);
            }
            $message .= ' (' . implode(', ', $args) . ')';
        }
        
        return new $class($message, $innerException);
    }

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