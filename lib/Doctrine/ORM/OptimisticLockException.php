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
 * An OptimisticLockException is thrown when a version check on an object
 * that uses optimistic locking through a version field fails.
 *
 * @author Roman Borschel <roman@code-factory.org>
 * @author Benjamin Eberlei <kontakt@beberlei.de>
 * @since 2.0
 */
class OptimisticLockException extends ORMException
{
    private $entity;

    public function __construct($msg, $entity)
    {
        $this->entity = $entity;
    }

    /**
     * Gets the entity that caused the exception.
     *
     * @return object
     */
    public function getEntity()
    {
        return $this->entity;
    }

    public static function lockFailed($entity)
    {
        return new self("The optimistic lock on an entity failed.", $entity);
    }

    public static function lockFailedVersionMissmatch($entity, $expectedLockVersion, $actualLockVersion)
    {
        return new self("The optimistic lock failed, version " . $expectedLockVersion . " was expected, but is actually ".$actualLockVersion, $entity);
    }

    public static function notVersioned($entityName)
    {
        return new self("Cannot obtain optimistic lock on unversioned entity " . $entityName, null);
    }
}