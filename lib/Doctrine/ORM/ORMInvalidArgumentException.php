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

/**
 * Contains exception messages for all invalid lifecycle state exceptions inside UnitOfWork
 *
 * @author Benjamin Eberlei <kontakt@beberlei.de>
 */
class ORMInvalidArgumentException extends \InvalidArgumentException
{
    static public function scheduleInsertForManagedEntity($entity)
    {
        return new self("A managed+dirty entity " . self::objToStr($entity) . " can not be scheduled for insertion.");
    }

    static public function scheduleInsertForRemovedEntity($entity)
    {
        return new self("Removed entity " . self::objToStr($entity) . " can not be scheduled for insertion.");
    }

    static public function scheduleInsertTwice($entity)
    {
        return new self("Entity " . self::objToStr($entity) . " can not be scheduled for insertion twice.");
    }

    static public function entityWithoutIdentity($className, $entity)
    {
        throw new self(
            "The given entity of type '" . $className . "' (".self::objToStr($entity).") has no identity/no " . 
            "id values set. It cannot be added to the identity map."
        );
    }

    static public function readOnlyRequiresManagedEntity($entity)
    {
        return new self("Only managed entities can be marked or checked as read only. But " . self::objToStr($entity) . " is not");
    }

    static public function newEntityFoundThroughRelationship(array $assoc, $entry)
    {
        return new self("A new entity was found through the relationship '"
                            . $assoc['sourceEntity'] . "#" . $assoc['fieldName'] . "' that was not"
                            . " configured to cascade persist operations for entity: " . self::objToStr($entry) . "."
                            . " To solve this issue: Either explicitly call EntityManager#persist()"
                            . " on this unknown entity or configure cascade persist "
                            . " this association in the mapping for example @ManyToOne(..,cascade={\"persist\"}). "
                            . " If you cannot find out which entity causes the problem"
                            . " implement '" . $assoc['targetEntity'] . "#__toString()' to get a clue.");
    }

    static public function detachedEntityFoundThroughRelationship(array $assoc, $entry)
    {
        throw new self("A detached entity of type " . $assoc['targetEntity'] . " (" . self::objToStr($entry) . ") "
                        . " was found through the relationship '" . $assoc['sourceEntity'] . "#" . $assoc['fieldName'] . "' "
                        . "during cascading a persist operation.");
    }

    static public function entityNotManaged($entity)
    {
        throw new self("Entity " . self::objToStr($entity) . " is not managed. An entity is managed if its fetched " .
                "from the database or registered as new through EntityManager#persist");
    }

    static public function entityHasNoIdentity($entity, $operation)
    {
        throw new self("Entity has no identity, therefore " . $operation ." cannot be performed. " . self::objToStr($entity));
    }

    static public function entityIsRemoved($entity, $operation)
    {
        throw new self("Entity is removed, therefore " . $operation ." cannot be performed. " . self::objToStr($entity));
    }

    static public function detachedEntityCannot($entity, $operation)
    {
        throw new self("A detached entity was found during " . $operation . " " . self::objToStr($entity));
    }

    /**
     * Helper method to show an object as string.
     *
     * @param  object $obj
     * @return string
     */
    private static function objToStr($obj)
    {
        return method_exists($obj, '__toString') ? (string)$obj : get_class($obj).'@'.spl_object_hash($obj);
    }
}