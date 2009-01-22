<?php

namespace Doctrine\ORM\Id;

/**
 * Special generator for application-assigned identifiers (doesnt really generate anything).
 *
 * @since 2.0
 * @author Roman Borschel <roman@code-factory.org>
 */
class Assigned extends AbstractIdGenerator
{
    /**
     * Returns the identifier assigned to the given entity.
     *
     * @param Doctrine\ORM\Entity $entity
     * @return mixed
     * @override
     */
    public function generate($entity)
    {
        $class = $this->_em->getClassMetadata(get_class($entity));
        if ($class->isIdentifierComposite()) {
            $identifier = array();
            $idFields = $class->getIdentifierFieldNames();
            foreach ($idFields as $idField) {
                $identifier[] =
                $value = $class->getReflectionProperty($idField)->getValue($entity);
                if (isset($value)) {
                    $identifier[] = $value;
                }
            }
        } else {
            $value = $class->getReflectionProperty($class->getSingleIdentifierFieldName())
                    ->getValue($entity);
            if (isset($value)) {
                $identifier = array($value);
            }
        }

        if ( ! $identifier) {
            throw new Doctrine_Exception("Entity '$entity' is missing an assigned ID.");
        }
        
        return $identifier;
    }
}

