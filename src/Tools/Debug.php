<?php

declare(strict_types=1);

namespace Doctrine\ORM\Tools;

use ArrayIterator;
use ArrayObject;
use DateTimeInterface;
use Doctrine\Common\Collections\Collection;
use Doctrine\ORM\Proxy\DefaultProxyClassNameResolver;
use Doctrine\Persistence\Proxy;
use stdClass;

use function array_keys;
use function count;
use function end;
use function explode;
use function extension_loaded;
use function get_class;
use function html_entity_decode;
use function ini_get;
use function ini_set;
use function is_array;
use function is_object;
use function ob_end_clean;
use function ob_get_contents;
use function ob_start;
use function strip_tags;
use function var_dump;

/**
 * Static class containing most used debug methods.
 *
 * @internal
 *
 * @link   www.doctrine-project.org
 */
final class Debug
{
    /**
     * Private constructor (prevents instantiation).
     */
    private function __construct()
    {
    }

    /**
     * Prints a dump of the public, protected and private properties of $var.
     *
     * @link https://xdebug.org/
     *
     * @param mixed $var      The variable to dump.
     * @param int   $maxDepth The maximum nesting level for object properties.
     */
    public static function dump($var, int $maxDepth = 2): string
    {
        $html = ini_get('html_errors');

        if ($html !== '1') {
            ini_set('html_errors', 'on');
        }

        if (extension_loaded('xdebug')) {
            $previousDepth = ini_get('xdebug.var_display_max_depth');
            ini_set('xdebug.var_display_max_depth', (string) $maxDepth);
        }

        try {
            $var = self::export($var, $maxDepth);

            ob_start();
            var_dump($var);

            $dump = ob_get_contents();

            ob_end_clean();

            $dumpText = strip_tags(html_entity_decode($dump));
        } finally {
            ini_set('html_errors', $html);

            if (isset($previousDepth)) {
                ini_set('xdebug.var_display_max_depth', $previousDepth);
            }
        }

        return $dumpText;
    }

    /**
     * @param mixed $var
     *
     * @return mixed
     */
    public static function export($var, int $maxDepth)
    {
        if ($var instanceof Collection) {
            $var = $var->toArray();
        }

        if (! $maxDepth) {
            return is_object($var) ? get_class($var)
                : (is_array($var) ? 'Array(' . count($var) . ')' : $var);
        }

        if (is_array($var)) {
            $return = [];

            foreach ($var as $k => $v) {
                $return[$k] = self::export($v, $maxDepth - 1);
            }

            return $return;
        }

        if (! is_object($var)) {
            return $var;
        }

        $return = new stdClass();
        if ($var instanceof DateTimeInterface) {
            $return->__CLASS__ = get_class($var);
            $return->date      = $var->format('c');
            $return->timezone  = $var->getTimezone()->getName();

            return $return;
        }

        $return->__CLASS__ = DefaultProxyClassNameResolver::getClass($var);

        if ($var instanceof Proxy) {
            $return->__IS_PROXY__          = true;
            $return->__PROXY_INITIALIZED__ = $var->__isInitialized();
        }

        if ($var instanceof ArrayObject || $var instanceof ArrayIterator) {
            $return->__STORAGE__ = self::export($var->getArrayCopy(), $maxDepth - 1);
        }

        return self::fillReturnWithClassAttributes($var, $return, $maxDepth);
    }

    /**
     * Fill the $return variable with class attributes
     * Based on obj2array function from {@see https://secure.php.net/manual/en/function.get-object-vars.php#47075}
     *
     * @param object $var
     *
     * @return mixed
     */
    private static function fillReturnWithClassAttributes($var, stdClass $return, int $maxDepth)
    {
        $clone = (array) $var;

        foreach (array_keys($clone) as $key) {
            $aux  = explode("\0", (string) $key);
            $name = end($aux);
            if ($aux[0] === '') {
                $name .= ':' . ($aux[1] === '*' ? 'protected' : $aux[1] . ':private');
            }

            $return->$name = self::export($clone[$key], $maxDepth - 1);
        }

        return $return;
    }
}
