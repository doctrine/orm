<?php if( $tool->getOption('section') || $tool->getOption('one-page') ): ?>

  <h1>Table of Contents</h1>

  <?php $tool->renderToc(); ?>

  <p>
    <?php if($tool->getOption('one-page')): ?>
      <a href="<?php echo ($tool->getOption('clean-url') ? "chapter/" : '?chapter=') . $tool->findByIndex('1.')->getPath(); ?>">View one chapter per page</a>
    <?php else: ?>
      <a href="<?php echo ($tool->getOption('clean-url') ? "one-page" : '?one-page=1') . '#' . $tool->getOption('section')->getPath(); ?>">View all in one page</a>
    <?php endif; ?>
  </p>
<?php else: ?>
  <p>
    You can view this manual online as
    <ul>
      <li><a href="<?php echo $tool->getOption('clean-url') ? "one-page" : '?one-page=1'; ?>">everything on a single page</a></li>
      <li><a href="<?php echo $tool->makeUrl($tool->findByIndex('1.')->getPath()); ?>">one chapter per page</a></li>
    </ul>
  </p>
<?php endif; ?>