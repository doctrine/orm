[?php

<?php if ($this->_hasNamespace($metadata)): ?>
namespace <?php echo $this->_getNamespace($metadata) ?>;
<?php endif; ?>

<?php if ($this->_extendsClass()): ?>
use <?php echo $this->_getClassToExtendNamespace() ?>;
<?php endif; ?>

/**
 * <?php echo $this->_getEntityAnnotation($metadata)."\n"; ?>
 * <?php echo $this->_getTableAnnotation($metadata)."\n" ?>
 * <?php echo $this->_getInheritanceAnnotation($metadata)."\n" ?>
 * <?php echo $this->_getDiscriminatorColumnAnnotation($metadata)."\n" ?>
 * <?php echo $this->_getDiscriminatorMapAnnotation($metadata)."\n" ?>
 */
class <?php echo $this->_getClassName($metadata); ?><?php if ($this->_extendsClass()): ?> extends <?php echo $this->_getClassToExtendName() ?><?php endif; ?>
{
<?php include('annotation_body.tpl.php') ?>
}