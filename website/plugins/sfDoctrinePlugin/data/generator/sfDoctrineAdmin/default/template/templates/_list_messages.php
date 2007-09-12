[?php if ($sf_request->getError('delete')): ?]
<div class="form-errors">
  <h2>[?php echo __('Could not delete the selected %name%', array('%name%' => '<?php echo sfInflector::humanize($this->getSingularName()) ?>')) ?]</h2>
  <ul>
    <li>[?php echo $sf_request->getError('delete') ?]</li>
  </ul>
</div>
[?php endif; ?]
