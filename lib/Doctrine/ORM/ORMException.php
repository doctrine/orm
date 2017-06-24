<?php declare(strict_types=1);

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

namespace Doctrine\ORM;

use Doctrine\Common\Cache\Cache as CacheDriver;
use Exception;

/**
 * Base exception class for all ORM exceptions.
 *
 * @author Roman Borschel <roman@code-factory.org>
 * @since 2.0
 */
class ORMException extends Exception
{
    /**
     * @return ORMException
     */
    public static function missingMappingDriverImpl(): ORMException
    {
        return new self("It's a requirement to specify a Metadata Driver and pass it ".
            "to Doctrine\\ORM\\Configuration::setMetadataDriverImpl().");
    }

    /**
     * @param string $queryName
     *
     * @return ORMException
     */
    public static function namedQueryNotFound(string $queryName): ORMException
    {
        return new self('Could not find a named query by the name "' . $queryName . '"');
    }

    /**
     * @param string $nativeQueryName
     *
     * @return ORMException
     */
    public static function namedNativeQueryNotFound(string $nativeQueryName): ORMException
    {
        return new self('Could not find a named native query by the name "' . $nativeQueryName . '"');
    }

    /**
     * @param object $entity
     * @param object $relatedEntity
     *
     * @return ORMException
     */
    public static function entityMissingForeignAssignedId($entity, $relatedEntity): ORMException
    {
        return new self(
            "Entity of type " . get_class($entity) . " has identity through a foreign entity " . get_class($relatedEntity) . ", " .
            "however this entity has no identity itself. You have to call EntityManager#persist() on the related entity " .
            "and make sure that an identifier was generated before trying to persist '" . get_class($entity) . "'. In case " .
            "of Post Insert ID Generation (such as MySQL Auto-Increment) this means you have to call " .
            "EntityManager#flush() between both persist operations."
        );
    }

    /**
     * @param object $entity
     * @param string $field
     *
     * @return ORMException
     */
    public static function entityMissingAssignedIdForField($entity, $field): ORMException
    {
        return new self("Entity of type " . get_class($entity) . " is missing an assigned ID for field  '" . $field . "'. " .
            "The identifier generation strategy for this entity requires the ID field to be populated before ".
            "EntityManager#persist() is called. If you want automatically generated identifiers instead " .
            "you need to adjust the metadata mapping accordingly."
        );
    }

    /**
     * @param string $field
     *
     * @return ORMException
     */
    public static function unrecognizedField(string $field): ORMException
    {
        return new self("Unrecognized field: $field");
    }

    /**
     *
     * @param string $class
     * @param string $association
     * @param string $given
     * @param string $expected
     *
     * @return \Doctrine\ORM\ORMInvalidArgumentException
     */
    public static function unexpectedAssociationValue(string $class, string $association, string $given, string $expected): \Doctrine\ORM\ORMInvalidArgumentException
    {
        return new self(sprintf('Found entity of type %s on association %s#%s, but expecting %s', $given, $class, $association, $expected));
    }

    /**
     * @param string $className
     * @param string $field
     *
     * @return ORMException
     */
    public static function invalidOrientation(string $className, string $field): ORMException
    {
        return new self("Invalid order by orientation specified for " . $className . "#" . $field);
    }

    /**
     * @param string $mode
     *
     * @return ORMException
     */
    public static function invalidFlushMode(string $mode): ORMException
    {
        return new self("'$mode' is an invalid flush mode.");
    }

    /**
     * @return ORMException
     */
    public static function entityManagerClosed(): ORMException
    {
        return new self("The EntityManager is closed.");
    }

    /**
     * @param string $mode
     *
     * @return ORMException
     */
    public static function invalidHydrationMode(string $mode): ORMException
    {
        return new self("'$mode' is an invalid hydration mode.");
    }

    /**
     * @return ORMException
     */
    public static function mismatchedEventManager(): ORMException
    {
        return new self("Cannot use different EventManager instances for EntityManager and Connection.");
    }

    /**
     * @param string $methodName
     *
     * @return ORMException
     */
    public static function findByRequiresParameter(string $methodName): ORMException
    {
        return new self("You need to pass a parameter to '".$methodName."'");
    }

    /**
     * @param string $entityName
     * @param string $fieldName
     * @param string $method
     *
     * @return ORMException
     */
    public static function invalidFindByCall(string $entityName, string $fieldName, string $method): ORMException
    {
        return new self(
            "Entity '".$entityName."' has no field '".$fieldName."'. ".
            "You can therefore not call '".$method."' on the entities' repository"
        );
    }

    /**
     * @param string $entityName
     * @param string $fieldName
     * @param string $method
     *
     * @return ORMException
     */
    public static function invalidMagicCall(string $entityName, string $fieldName, string $method): ORMException
    {
        return new self(
            "Entity '".$entityName."' has no field '".$fieldName."'. ".
            "You can therefore not call '".$method."' on the entities' repository"
        );
    }

    /**
     * @param string $entityName
     * @param string $associationFieldName
     *
     * @return ORMException
     */
    public static function invalidFindByInverseAssociation(string $entityName, string $associationFieldName): ORMException
    {
        return new self(
            "You cannot search for the association field '".$entityName."#".$associationFieldName."', ".
            "because it is the inverse side of an association. Find methods only work on owning side associations."
        );
    }

    /**
     * @return ORMException
     */
    public static function invalidResultCacheDriver(): ORMException
    {
        return new self("Invalid result cache driver; it must implement Doctrine\\Common\\Cache\\Cache.");
    }

    /**
     * @return ORMException
     */
    public static function notSupported(): ORMException
    {
        return new self("This behaviour is (currently) not supported by Doctrine 2");
    }

    /**
     * @return ORMException
     */
    public static function queryCacheNotConfigured(): ORMException
    {
        return new self('Query Cache is not configured.');
    }

    /**
     * @return ORMException
     */
    public static function metadataCacheNotConfigured(): ORMException
    {
        return new self('Class Metadata Cache is not configured.');
    }

    /**
     * @param \Doctrine\Common\Cache\Cache $cache
     *
     * @return ORMException
     */
    public static function queryCacheUsesNonPersistentCache(CacheDriver $cache): ORMException
    {
        return new self('Query Cache uses a non-persistent cache driver, ' . get_class($cache) . '.');
    }

    /**
     * @param \Doctrine\Common\Cache\Cache $cache
     *
     * @return ORMException
     */
    public static function metadataCacheUsesNonPersistentCache(CacheDriver $cache): ORMException
    {
        return new self('Metadata Cache uses a non-persistent cache driver, ' . get_class($cache) . '.');
    }

    /**
     * @return ORMException
     */
    public static function proxyClassesAlwaysRegenerating(): ORMException
    {
        return new self('Proxy Classes are always regenerating.');
    }

    /**
     * @param string $entityNamespaceAlias
     *
     * @return ORMException
     */
    public static function unknownEntityNamespace(string $entityNamespaceAlias): ORMException
    {
        return new self(
            "Unknown Entity namespace alias '$entityNamespaceAlias'."
        );
    }

    /**
     * @param string $className
     *
     * @return ORMException
     */
    public static function invalidEntityRepository(string $className): ORMException
    {
        return new self("Invalid repository class '".$className."'. It must be a Doctrine\Common\Persistence\ObjectRepository.");
    }

    /**
     * @param string $className
     * @param string $fieldName
     *
     * @return ORMException
     */
    public static function missingIdentifierField(string $className, string $fieldName): ORMException
    {
        return new self("The identifier $fieldName is missing for a query of " . $className);
    }

    /**
     * @param string $className
     * @param string[] $fieldNames
     *
     * @return ORMException
     */
    public static function unrecognizedIdentifierFields(string $className, $fieldNames): ORMException
    {
        return new self(
            "Unrecognized identifier fields: '" . implode("', '", $fieldNames) . "' " .
            "are not present on class '" . $className . "'."
        );
    }

    /**
     * @return ORMException
     */
    public static function cantUseInOperatorOnCompositeKeys(): ORMException
    {
        return new self("Can't use IN operator on entities that have composite keys.");
    }
}
