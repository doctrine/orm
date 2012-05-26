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

namespace Doctrine\ORM\Mapping;

/**
 * A set of rules for determining the physical column and table names
 *
 * 
 * @link    www.doctrine-project.org
 * @since   2.3
 * @author  Fabio B. Silva <fabio.bat.silva@gmail.com>
 */
interface NamingStrategy
{
    /**
     * Return a table name for an entity class
     *
     * @param string $className The fully-qualified class name
     * @return string A table name
     */
    function classToTableName($className);

    /**
     * Return a column name for a property
     *
     * @param string $propertyName A property
     * @return string A column name
     */
    function propertyToColumnName($propertyName);

    /**
     * Return the default reference column name
     *
     * @return string A column name
     */
    function referenceColumnName();

    /**
     * Return a join column name for a property
     *
     * @param string $propertyName A property
     * @return string A join column name
     */
    function joinColumnName($propertyName);

    /**
     * Return a join table name
     *
     * @param string $sourceEntity The source entity
     * @param string $targetEntity The target entity
     * @param string $propertyName A property
     * @return string A join table name
     */
    function joinTableName($sourceEntity, $targetEntity, $propertyName = null);

    /**
     * Return the foreign key column name for the given parameters
     *
     * @param string $entityName A entity
     * @param string $referencedColumnName A property
     * @return string A join column name
     */
    function joinKeyColumnName($entityName, $referencedColumnName = null);
}
