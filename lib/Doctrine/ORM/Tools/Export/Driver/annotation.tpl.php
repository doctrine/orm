[?php

<?php if ($this->hasNamespace($metadata)): ?>

namespace <?php echo $this->getNamespace($metadata) ?>;
<?php endif; ?>
<?php if ($this->extendsClass()): ?>

use <?php echo $this->getClassToExtendNamespace() ?>;
<?php endif; ?>

/**
<?php if ($metadata->isMappedSuperclass): ?>
 * @MappedSuperclass
<?php else: ?>
 * @Entity
<?php endif; ?>
 * <?php echo $this->getTableAnnotation($metadata)."\n" ?>
 */
class <?Php echo $this->getClassName($metadata) ?><?php if ($this->extendsClass()): ?> extends <?php echo $this->getClassToExtendName() ?><?php endif; ?>

{
<?php include('annotation_body.tpl.php') ?>
}