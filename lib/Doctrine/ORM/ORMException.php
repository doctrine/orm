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
 * and is licensed under the LGPL. For more information, see
 * <http://www.doctrine-project.org>.
 */

namespace Doctrine\ORM;

use Exception;

/**
 * Base exception class for all ORM exceptions.
 *
 * @author Roman Borschel <roman@code-factory.org>
 * @since 2.0
 */
class ORMException extends Exception
{
    public static function missingMappingDriverImpl()
    {
        return new self("It's a requirement to specify a Metadata Driver and pass it ".
            "to Doctrine\ORM\Configuration::setMetadataDriverImpl().");
    }

    public static function entityMissingAssignedId($entity)
    {
        return new self("Entity of type " . get_class($entity) . " is missing an assigned ID.");
    }

    public static function unrecognizedField($field)
    {
        return new self("Unrecognized field: $field");
    }

    public static function invalidFlushMode($mode)
    {
        return new self("'$mode' is an invalid flush mode.");
    }

    public static function entityManagerClosed()
    {
        return new self("The EntityManager is closed.");
    }

    public static function invalidHydrationMode($mode)
    {
        return new self("'$mode' is an invalid hydration mode.");
    }

    public static function mismatchedEventManager()
    {
        return new self("Cannot use different EventManager instances for EntityManager and Connection.");
    }

    public static function findByRequiresParameter($methodName)
    {
        return new self("You need to pass a parameter to '".$methodName."'");
    }

    public static function invalidFindByCall($entityName, $fieldName, $method)
    {
        return new self(
            "Entity '".$entityName."' has no field '".$fieldName."'. ".
            "You can therefore not call '".$method."' on the entities' repository"
        );
    }

    public static function invalidResultCacheDriver() {
        return new self("Invalid result cache driver; it must implement \Doctrine\Common\Cache\Cache.");
    }

    public static function notSupported() {
        return new self("This behaviour is (currently) not supported by Doctrine 2");
    }

    public static function queryCacheNotConfigured()
    {
        return new self('Query Cache is not configured.');
    }

    public static function metadataCacheNotConfigured()
    {
        return new self('Class Metadata Cache is not configured.');
    }

    public static function proxyClassesAlwaysRegenerating()
    {
        return new self('Proxy Classes are always regenerating.');
    }

    public static function unknownEntityNamespace($entityNamespaceAlias)
    {
        return new self(
            "Unknown Entity namespace alias '$entityNamespaceAlias'."
        );
    }
}
