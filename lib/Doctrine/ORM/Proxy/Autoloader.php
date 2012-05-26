<?php
/*
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
 * and is licensed under the MIT license. For more information, see
 * <http://www.doctrine-project.org>.
 */

namespace Doctrine\ORM\Proxy;

/**
 * Special Autoloader for Proxy classes because them not being PSR-0 compatible.
 *
 * @author Benjamin Eberlei <kontakt@beberlei.de>
 */
class Autoloader
{
    /**
     * Resolve proxy class name to a filename based on the following pattern.
     *
     * 1. Remove Proxy namespace from class name
     * 2. Remove namespace seperators from remaining class name.
     * 3. Return PHP filename from proxy-dir with the result from 2.
     *
     * @param string $proxyDir
     * @param string $proxyNamespace
     * @param string $className
     * @return string
     */
    static public function resolveFile($proxyDir, $proxyNamespace, $className)
    {
        if (0 !== strpos($className, $proxyNamespace)) {
            throw ProxyException::notProxyClass($className, $proxyNamespace);
        }

        $className = str_replace('\\', '', substr($className, strlen($proxyNamespace) +1));
        return $proxyDir . DIRECTORY_SEPARATOR . $className.'.php';
    }

    /**
     * Register and return autoloader callback for the given proxy dir and
     * namespace.
     *
     * @param string $proxyDir
     * @param string $proxyNamespace
     * @param Closure $notFoundCallback Invoked when the proxy file is not found.
     * @return Closure
     */
    static public function register($proxyDir, $proxyNamespace, \Closure $notFoundCallback = null)
    {
        $proxyNamespace = ltrim($proxyNamespace, "\\");
        $autoloader = function($className) use ($proxyDir, $proxyNamespace, $notFoundCallback) {
            if (0 === strpos($className, $proxyNamespace)) {
                $file = Autoloader::resolveFile($proxyDir, $proxyNamespace, $className);

                if ($notFoundCallback && ! file_exists($file)) {
                    $notFoundCallback($proxyDir, $proxyNamespace, $className);
                }

                require $file;
            }
        };
        spl_autoload_register($autoloader);
        return $autoloader;
    }
}

