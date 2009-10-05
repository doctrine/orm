<?php foreach ($metadata->fieldMappings as $fieldMapping): ?><?php if ($this->hasProperty($fieldMapping['fieldName'], $metadata)) continue; ?>
<?php echo $this->getFieldMappingAnnotation($fieldMapping, $metadata)."\n" ?>
<?php echo str_repeat(' ', $this->_numSpaces) ?>private $<?php echo $fieldMapping['fieldName'] ?>;

<?php endforeach ?>
<?php foreach ($metadata->associationMappings as $associationMapping): ?><?php if ($this->hasProperty($associationMapping->sourceFieldName, $metadata)) continue; ?>
<?php echo $this->getAssociationMappingAnnotation($associationMapping, $metadata)."\n" ?>
<?php echo str_repeat(' ', $this->_numSpaces) ?>private $<?php echo $associationMapping->sourceFieldName ?><?php if ($associationMapping->isManyToMany()): ?> = array() <?php endif; ?>;

<?php endforeach; ?>
<?php foreach ($this->getMethods($metadata) as $method): ?>
<?php echo $method ?>
<?php endforeach ?>