<?php

declare(strict_types = 1);

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

namespace Doctrine\ORM\Mapping\Factory;

use InvalidArgumentException;

class Autoloader
{
    /**
     * Resolves ClassMetadata class name to a filename based on the following pattern.
     *
     * 1. Remove Metadata namespace from class name.
     * 2. Remove namespace separators from remaining class name.
     * 3. Return PHP filename from metadata-dir with the result from 2.
     *
     * @param string $metadataDir
     * @param string $metadataNamespace
     * @param string $className
     *
     * @return string
     *
     * @throws InvalidArgumentException
     */
    public static function resolveFile(string $metadataDir, string $metadataNamespace, string $className) : string
    {
        if (0 !== strpos($className, $metadataNamespace)) {
            throw new InvalidArgumentException(
                sprintf('The class "%s" is not part of the proxy namespace "%s"', $className, $metadataNamespace)
            );
        }

        // remove metadata namespace from class name
        $classNameRelativeToProxyNamespace = substr($className, strlen($metadataNamespace));

        // remove namespace separators from remaining class name
        $fileName = str_replace('\\', '', $classNameRelativeToProxyNamespace);

        return $metadataDir . DIRECTORY_SEPARATOR . $fileName . '.php';
    }

    /**
     * Registers and returns autoloader callback for the given metadata dir and namespace.
     *
     * @param string        $metadataDir
     * @param string        $metadataNamespace
     * @param callable|null $notFoundCallback Invoked when the proxy file is not found.
     *
     * @return \Closure
     *
     * @throws InvalidArgumentException
     */
    public static function register(
        string $metadataDir,
        string $metadataNamespace,
        callable $notFoundCallback = null
    ) : \Closure
    {
        $metadataNamespace = ltrim($metadataNamespace, '\\');

        if ( ! (null === $notFoundCallback || is_callable($notFoundCallback))) {
            $type = is_object($notFoundCallback) ? get_class($notFoundCallback) : gettype($notFoundCallback);

            throw new InvalidArgumentException(
                sprintf('Invalid \$notFoundCallback given: must be a callable, "%s" given', $type)
            );
        }

        $autoloader = function ($className) use ($metadataDir, $metadataNamespace, $notFoundCallback) {
            if (0 === strpos($className, $metadataNamespace)) {
                $file = Autoloader::resolveFile($metadataDir, $metadataNamespace, $className);

                if ($notFoundCallback && ! file_exists($file)) {
                    call_user_func($notFoundCallback, $metadataDir, $metadataNamespace, $className);
                }

                require $file;
            }
        };

        spl_autoload_register($autoloader);

        return $autoloader;
    }
}
