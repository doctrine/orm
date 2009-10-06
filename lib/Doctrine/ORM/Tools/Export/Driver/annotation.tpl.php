[?php

<?php if ($this->_hasNamespace($metadata)): ?>

namespace <?php echo $this->_getNamespace($metadata) ?>;
<?php endif; ?>
<?php if ($this->_extendsClass()): ?>

use <?php echo $this->_getClassToExtendNamespace() ?>;
<?php endif; ?>

/**
<?php if ($metadata->isMappedSuperclass): ?>
 * @MappedSuperclass
<?php else: ?>
 * @Entity
<?php endif; ?>
 * <?php echo $this->_getTableAnnotation($metadata)."\n" ?>
 */
class <?Php echo $this->_getClassName($metadata) ?><?php if ($this->_extendsClass()): ?> extends <?php echo $this->_getClassToExtendName() ?><?php endif; ?>

{
<?php include('annotation_body.tpl.php') ?>
}