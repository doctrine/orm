<?php
/*
 *  $Id$
 *
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
 * <http://www.phpdoctrine.org>.
 */

#namespace Doctrine\ORM\Exceptions;

/**
 * A MappingException indicates that something is wrong with the mapping setup.
 *
 * @since 2.0
 */
class Doctrine_ORM_Exceptions_MappingException extends Doctrine_ORM_Exceptions_ORMException
{
    public static function identifierRequired($entityName)
    {
        return new self("No identifier/primary key specified for Entity '$entityName'."
                . " Every Entity must have an identifier/primary key.");
    }
    
    public static function invalidInheritanceType($type)
    {
        return new self("The inheritance type '$type' does not exist.");
    }
    
    public static function generatorNotAllowedWithCompositeId()
    {
        return new self("Id generators can't be used with a composite id.");
    }
    
    public static function missingFieldName()
    {
        return new self("The association mapping misses the 'fieldName' attribute.");
    }
    
    public static function missingTargetEntity($fieldName)
    {
        return new self("The association mapping '$fieldName' misses the 'targetEntity' attribute.");
    }
    
    public static function missingSourceEntity($fieldName)
    {
        return new self("The association mapping '$fieldName' misses the 'sourceEntity' attribute.");
    }
    
    public static function mappingNotFound($fieldName)
    {
        return new self("No mapping found for field '$fieldName'.");
    }
    
    public static function oneToManyRequiresMappedBy($fieldName)
    {
        return new self("OneToMany mapping on field '$fieldName' requires the 'mappedBy' attribute.");
    }
    
    public static function joinTableRequired($fieldName)
    {
        return new self("The mapping of field '$fieldName' requires an the 'joinTable' attribute.");
    }
    
    /**
     * Generic exception for invalid mappings.
     *
     * @param string $fieldName
     */
    public static function invalidMapping($fieldName)
    {
        return new self("The mapping of field '$fieldName' is invalid.");
    }
}

?>