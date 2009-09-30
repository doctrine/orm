[?php

namespace <?php echo $metadata->namespace ?>;

/**
<?php if ($metadata->isMappedSuperclass): ?>
 * @MappedSuperclass
<?php else: ?>
 * @Entity
<?php endif; ?>
 * <?php echo $this->_getTableAnnotation($metadata)."\n" ?>
 */
class <?Php echo $metadata->getReflectionClass()->getShortName()."\n" ?>
{
<?php foreach ($metadata->fieldMappings as $fieldMapping): ?>
<?php echo $this->_getFieldMappingAnnotation($fieldMapping, $metadata)."\n" ?>
    private $<?php echo $fieldMapping['fieldName'] ?>;

<?php endforeach ?>
<?php foreach ($metadata->associationMappings as $associationMapping): ?>
<?php echo $this->_getAssociationMappingAnnotation($associationMapping, $metadata)."\n" ?>
    private $<?php echo $associationMapping->sourceFieldName ?>;

<?php endforeach; ?>

}