<?php


declare(strict_types=1);

namespace Doctrine\ORM\Proxy\Factory;

use InvalidArgumentException;

/**
 * Special Autoloader for Proxy classes, which are not PSR-0 compliant.
 *
 * @author Benjamin Eberlei <kontakt@beberlei.de>
 */
class Autoloader
{
    /**
     * Resolves proxy class name to a filename based on the following pattern.
     *
     * 1. Remove Proxy namespace from class name.
     * 2. Remove namespace separators from remaining class name.
     * 3. Return PHP filename from proxy-dir with the result from 2.
     *
     * @param string $proxyDir
     * @param string $proxyNamespace
     * @param string $className
     *
     * @return string
     *
     * @throws InvalidArgumentException
     */
    public static function resolveFile(string $proxyDir, string $proxyNamespace, string $className) : string
    {
        if (0 !== strpos($className, $proxyNamespace)) {
            throw new InvalidArgumentException(
                sprintf('The class "%s" is not part of the proxy namespace "%s"', $className, $proxyNamespace)
            );
        }

        // remove proxy namespace from class name
        $classNameRelativeToProxyNamespace = substr($className, strlen($proxyNamespace));

        // remove namespace separators from remaining class name
        $fileName = str_replace('\\', '', $classNameRelativeToProxyNamespace);

        return $proxyDir . DIRECTORY_SEPARATOR . $fileName . '.php';
    }

    /**
     * Registers and returns autoloader callback for the given proxy dir and namespace.
     *
     * @param string        $proxyDir
     * @param string        $proxyNamespace
     * @param callable|null $notFoundCallback Invoked when the proxy file is not found.
     *
     * @return \Closure
     *
     * @throws InvalidArgumentException
     */
    public static function register(
        string $proxyDir,
        string $proxyNamespace,
        callable $notFoundCallback = null
    ) : \Closure
    {
        $proxyNamespace = ltrim($proxyNamespace, '\\');

        if (! (null === $notFoundCallback || is_callable($notFoundCallback))) {
            $type = is_object($notFoundCallback) ? get_class($notFoundCallback) : gettype($notFoundCallback);

            throw new InvalidArgumentException(
                sprintf('Invalid \$notFoundCallback given: must be a callable, "%s" given', $type)
            );
        }

        $autoloader = function ($className) use ($proxyDir, $proxyNamespace, $notFoundCallback) {
            if (0 === strpos($className, $proxyNamespace)) {
                $file = Autoloader::resolveFile($proxyDir, $proxyNamespace, $className);

                if ($notFoundCallback && ! file_exists($file)) {
                    call_user_func($notFoundCallback, $proxyDir, $proxyNamespace, $className);
                }

                require $file;
            }
        };

        spl_autoload_register($autoloader);

        return $autoloader;
    }
}
